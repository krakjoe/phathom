<?php declare(strict_types=1);
/*
  +----------------------------------------------------------------------+
  | phathom                                                              |
  +----------------------------------------------------------------------+
  | Copyright (c) Joe Watkins 2026                                       |
  +----------------------------------------------------------------------+
  | This source file is subject to the BSD 3-Clause License bundled     |
  | with this package in the file LICENSE.                               |
  +----------------------------------------------------------------------+
  | Author: krakjoe                                                      |
  +----------------------------------------------------------------------+
 */

namespace pharos\phathom\Earley {
    use \pharos\phathom\Grammar;
    use \pharos\phathom\Grammar\Interface;
    use \pharos\phathom\Grammar\Optimization;
    use \pharos\phathom\Grammar\Symbol;

    /*
    * Earley characteristic automaton — built AOT, used only to prewarm
    * the lexer pattern cache via Grammar\Optimize\Lexer.
    *
    * An LR(0)-style item-set closure (predict + complete) over the grammar
    * produces a graph of (rule, alt, dot) sets connected by terminal-labelled
    * scan edges.  The terminal set at the dot in each node is the exact set
    * scan() would receive at the corresponding chart position; calling
    * lexer->expect() for each such set pre-compiles every regex the lexer
    * will ever need.
    *
    * Unlike GLR\Automaton the tables are NOT needed at parse time — Earley
    * is not table-driven.  __serialize therefore returns [] and the expected
    * sets travel with the lexer pattern cache, not with this object.
    */
    final class Automaton implements Interface\Automaton {
        private const string RESERVE = "\$Automaton\$";

        private string $start;
        private array $rules    = [];
        private array $seen     = [];
        private array $items    = [];
        private array $nullable = [];
        private array $waiting  = [];
        private array $expected = [];

        public function __construct(Grammar $grammar) {
            $visited = [];
            $queue =
                $this->start($grammar);

            while (([$node, $context] = \array_pop($queue))) {
                if (!$this->visit($visited, $node)) {
                    continue;
                }

                $expected   = [];
                $successors = [];

                foreach ($node as $item) {
                    $symbol =
                        $item->alternative
                            ->symbols[$item->dot] ?? null;
                    if ($symbol === null) {
                        continue;
                    }

                    if (($type = $symbol->terminal) === false) {
                        continue;
                    }

                    $expected[$type] = true;
                    $successors[$type][] =
                        new Item(
                            rule:        $item->rule,
                            alt:         $item->alt,
                            dot:         $item->dot + 1,
                            origin:      0,
                            backs:       [],
                            alternative: $item->alternative);
                }

                if (!$expected) {
                    continue;
                }

                $this->expected[] = $expected;

                foreach ($this->update($node)
                            as $dotted => $items) {
                    $context[$dotted] = $items;
                }

                foreach ($successors as $items) {
                    $queue[] = [
                        $this->collect($items, $context),
                        $context,
                    ];
                }
            }

            unset($this->start);
            unset($this->rules);
            unset($this->seen);
            unset($this->items);
            unset($this->nullable);
            unset($this->waiting);
        }

        private function start(Grammar $grammar) : array {
            $this->start = $grammar->start;
            $this->rules = $grammar->rules;

            $initial = [];
            foreach ($this->rules
                        [$this->start] as $aid => $alt) {
                $initial[] = new Item(
                    rule:        $this->start,
                    alt:         $aid,
                    dot:         0,
                    origin:      0,
                    backs:       [],
                    alternative: $alt);
            }
            return [
                [$this->collect($initial), []]
            ];
        }

        /*
        * Returns items from the current node that have a non-terminal at the
        * dot, grouped by that non-terminal.  Each group replaces the
        * corresponding context entry, so completions in descendant nodes
        * advance items from the nearest ancestor that predicted it.
        */
        private function update(array $node) : array {
            $update = [];
            foreach ($node as $item) {
                $dotted =
                    $item->alternative
                        ->symbols[$item->dot] ?? null;
                if ($dotted === null) {
                    continue;
                }

                if ($dotted->terminal !== false) {
                    continue;
                }

                $update[$dotted->name]
                    [$item->rule]
                    [$item->alt]
                    [$item->dot] = $item;
            }
            return $update;
        }

        private function visit(array &$visited, array $node) : bool {
            $visit = &$visited;
            foreach ($node as $item) {
                $visit = &$visit
                    [$item->rule]
                    [$item->alt]
                    [$item->dot];
            }

            if (isset($visit[Automaton::RESERVE])) {
                return false;
            }

            return ($visit[Automaton::RESERVE] = true);
        }

        private function collect(array $seeds, array $context = []) : array {
            $this->seen     = [];
            $this->items    = [];
            $this->nullable = [];
            $this->waiting  = [];

            foreach ($context as $dotted => $rule) {
                foreach ($rule as $alt) {
                    foreach ($alt as $dot) {
                        foreach ($dot as $item) {
                            $this->waiting[$dotted][] = $item;
                        }
                    }
                }
            }

            foreach ($seeds as $item) {
                $this->add($item);
            }

            \usort($this->items,
                fn(Item $a, Item $b) =>
                    $a->rule <=> $b->rule ?:
                    $a->alt  <=> $b->alt  ?:
                    $a->dot  <=> $b->dot);

            return $this->items;
        }

        private function add(Item $item) : void {
            $slot = &$this->seen
                [$item->rule]
                [$item->alt]
                [$item->dot];

            if (isset($slot)) {
                return;
            }

            $this->items[] =
                $slot = $item;

            $dotted =
                $item->alternative
                    ->symbols[$item->dot] ?? null;

            if ($dotted === null) {
                /* Complete */
                $this->nullable
                    [$item->rule][] = $item;
                $this->complete($item);
                return;
            }

            if ($dotted->terminal !== false) {
                return; /* terminal — nothing to predict */
            }

            $this->waiting
                [$dotted->name][] = $item;
            $this->predict($dotted);
            $this->drain($dotted, $item);
        }

        private function predict(Symbol $dotted) : void {
            foreach ($this->rules[$dotted->name] as $aid => $alternative) {
                $this->add(new Item(
                    rule:        $dotted->name,
                    alt:         $aid,
                    dot:         0,
                    origin:      0,
                    backs:       [],
                    alternative: $alternative));
            }
        }

        private function complete(Item $item) : void {
            foreach ($this->waiting[$item->rule] ?? [] as $waiting) {
                $this->add(new Item(
                    rule:        $waiting->rule,
                    alt:         $waiting->alt,
                    dot:         $waiting->dot + 1,
                    origin:      0,
                    backs:       [],
                    alternative: $waiting->alternative));
            }
        }

        private function drain(Symbol $dotted, Item $item) : void {
            foreach ($this->nullable[$dotted->name] ?? [] as $completed) {
                $this->add(new Item(
                    rule:        $item->rule,
                    alt:         $item->alt,
                    dot:         $item->dot + 1,
                    origin:      0,
                    backs:       [],
                    alternative: $item->alternative));
            }
        }

        public function expected() : array {
            return $this->expected;
        }

        public function __serialize() : array {
            /* expected will be serial on lexer */
            return [];
        }

        public function __unserialize(array $array) : void {
            unset($this->start);
            unset($this->rules);
            unset($this->seen);
            unset($this->items);
            unset($this->nullable);
            unset($this->waiting);
        }
    }
}