<?php declare(strict_types=1);

namespace pharos\phathom\Earley\Optimize {
    use \pharos\phathom\Grammar\Optimization;
    use \pharos\phathom\Grammar\Symbol;
    use \pharos\phathom\Earley\Item;

    /*
    * We're going to prewarm the pattern cache on lexer by telling
    * it what it may expect at any call to scan for this grammar.
    * This achieves:
    *   no regex pattern allocations (in userland) during a parse
    *   the pattern cache is serialized complete with the grammar
    */
    final class Lexer extends Optimization {
        private const string OPTIMIZED = '@';

        private array $seen     = [];
        private array $items    = [];
        private array $nullable = [];
        private array $waiting  = [];

        /*
        * In detail we are building an Earl(e)y (AOT) characteristic automaton:
        *  (LR(0)-style item-set closure with predict+complete).
        * Each node in the automaton is a predict+complete-closed set of 
        *  (rule, alt, dot) triples;
        * edges are labelled by terminal consts (scan transitions).
        *
        * The expected set passed to scan() at any chart position is exactly
        * the set of terminal consts at the dot across all items in the
        * corresponding automaton node.
        */
        public function pass() : void {
            $optimized = [];
            [
                $optimize,
                $queue
            ] = $this->start();

            while (([$node, $context] = \array_pop($queue))) {
                if (!$this->optimize($optimized, $node)) {
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

                $optimize($expected);

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

        private function optimize(array &$optimized, array $node) : bool {
            $optimize = &$optimized;
            foreach ($node as $item) {
                $optimize = &$optimize
                    [$item->rule]
                    [$item->alt]
                    [$item->dot];
            }

            if (isset($optimize[Lexer::OPTIMIZED])) {
                return false;
            }

            return ($optimize[Lexer::OPTIMIZED] = true);
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

        private function start() : array {
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
                $this->lexer->expect(...),
                [
                    [$this->collect($initial), []]
                ]
            ];
        }
    }
}