<?php declare(strict_types=1);

namespace pharos\phathom\Earley\Optimize {
    use \pharos\phathom\Grammar\Optimization;
    use \pharos\phathom\Grammar\Symbol;
    use \pharos\phathom\Earley\Item;

    final class Lexer extends Optimization {
        private const string VISITED = '@';

        private \Closure $optimize;

        /* Per-collect state — reset at the top of each collect() call */
        private array $seen     = [];
        private array $items    = [];
        private array $nullable = [];
        private array $waiting  = [];

        public function pass() : void {
            /*
            * We're going to prewarm the pattern cache on lexer by telling
            * it what it may expect at any call to scan for this grammar.
            * This achieves:
            *   no regex pattern allocations (in userland) during a parse
            *   the pattern cache is serialized complete with the grammar
            */
            $this->optimize = 
                $this->lexer
                    ->expect(...);

            /* In detail we are building the Earley characteristic automaton:
             *  (LR(0)-style item-set closure with predict+complete).
             * Each node in the automaton is a predict+complete-closed set of 
             *  (rule, alt, dot) triples;
             * edges are labelled by terminal consts (scan transitions).
             *
             * The expected set passed to scan() at any chart position is exactly
             * the set of terminal consts at the dot across all items in the
             * corresponding automaton node.  Pre-warming the lexer pattern cache
             * for every such set guarantees that deserialization is always warm —
             * no cache miss can occur at parse time regardless of input.
             */
            $initial =
                $this->start();
            $visited = [];
            $queue   = [$this->collect($initial)];

            while (($state = \array_pop($queue))) {
                /* Canonical state key: items are already sorted by collect();
                 * walk the trie to detect revisited states without building strings */
                $node = &$visited;
                foreach ($state as $item) {
                    $node = &$node[$item->rule][$item->alt][$item->dot];
                }

                if (isset($node[Lexer::VISITED])) {
                    continue;
                }

                $node[Lexer::VISITED] = true;

                /* Collect expected terminals and group seeds for scan successors */
                $expected   = [];
                $successors = [];

                foreach ($state as $item) {
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

                if ($expected) {
                    ($this->optimize)($expected);
                    foreach ($successors as $items) {
                        $queue[] =
                            $this->collect($items);
                    }
                }
            }
        }

        private function collect(array $seeds) : array {
            $this->seen     = [];
            $this->items    = [];
            $this->nullable = [];
            $this->waiting  = [];

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
                $this->nullable[$item->rule][] = $item;
                $this->complete($item);
                return;
            }

            if ($dotted->terminal !== false) {
                return; /* terminal — nothing to predict */
            }

            $this->waiting[$dotted->name][] = $item;
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
            return $initial;
        }
    }
}