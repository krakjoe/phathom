<?php declare(strict_types=1);

namespace pharos\phathom\Earley\Optimize {
    use \pharos\phathom\Grammar\Optimization;
    use \pharos\phathom\Earley\Item;

    final class Lexer extends Optimization {
        private const string VISITED = '@';

        private \Closure $optimize;

        public function pass() : void {
            /*
            * We're going to prewarm the pattern cache on lexer by telling it what it may expect
            * at any call to scan for this grammar, this means that no strings are allocated for patterns
            * during parse time, and all patterns are serialized with the grammar.
            */
            $this->optimize = 
                $this->lexer
                    ->expect(...);

            /* Build the Earley characteristic automaton (LR(0)-style item-set
             * closure with predict+complete).  Each node in the automaton is a
             * predict+complete-closed set of (rule, alt, dot) triples; edges are
             * labelled by terminal consts (scan transitions).
             *
             * The expected set passed to scan() at any chart position is exactly
             * the set of terminal consts at the dot across all items in the
             * corresponding automaton node.  Pre-warming the lexer pattern cache
             * for every such set guarantees that deserialization is always warm —
             * no cache miss can occur at parse time regardless of input.
             */
            $initial = [];
            foreach ($this->rules[$this->start] as $aid => $alt) {
                $initial[] = new Item(
                    rule:        $this->start,
                    alt:         $aid,
                    dot:         0,
                    origin:      0,
                    backs:       [],
                    alternative: $alt);
            }

            $visited = [];
            $queue   = [$this->collect($initial)];

            while ($queue) {
                $state = \array_pop($queue);

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

        /**
         * Predict+complete closure over a seed set of (rule, alt, dot) triples.
         *
         * Mirrors the inner loop of Chart exactly: predict adds all alternatives
         * of any non-terminal at the dot; complete advances items that were waiting
         * for a rule that is already completed within the same state.  The "advance
         * past already-completed non-terminal" case (needed for ε-productions and
         * left-recursive synthetic rules) is handled by checking, at predict time,
         * whether any completed version of the predicted rule is already present.
         *
         * Every item is added at most once (guarded by the key map), so the
         * function always terminates.
         */
        private function collect(array $seeds) : array {
            $seen     = [];
            $items    = [];
            $worklist = [];

            $add = function(Item $item)
                        use (&$seen, &$items, &$worklist) : void {
                $slot = &$seen[$item->rule][$item->alt][$item->dot];
                if (!isset($slot)) {
                    $worklist[] =
                        $items[] =
                            $slot = $item;
                }
            };

            foreach ($seeds as $item) {
                $add($item);
            }

            while ($worklist) {
                $item = \array_pop($worklist);

                $symbols = $item->alternative->symbols;

                if ($item->dot < \count($symbols)) {
                    $symbol = $symbols[$item->dot];

                    if ($symbol->terminal !== false) {
                        continue; /* nothing to predict or advance */
                    }

                    /* Predict: add all alternatives of $symbol->name at dot=0 */
                    foreach ($this->rules[
                                $symbol->name] as 
                                    $aid => $alternative) {
                        $add(new Item(
                            rule:        $symbol->name,
                            alt:         $aid,
                            dot:         0,
                            origin:      0,
                            backs:       [],
                            alternative: $alternative));
                    }

                    /* Advance past already-completed $symbol->name: if any completed item
                     * for $symbol->name is already in the state, the current item can
                     * immediately advance past it (handles ε-productions and the
                     * left-recursive synthetic rules produced by quantifiers) */
                    foreach ($this->rules[
                                $symbol->name] as 
                                    $aid => $alternative) {
                        $dot = \count($alternative->symbols);
                        if (isset($seen[$symbol->name][$aid][$dot])) {
                            $add(new Item(
                                rule:        $item->rule,
                                alt:         $item->alt,
                                dot:         $item->dot + 1,
                                origin:      0,
                                backs:       [],
                                alternative: $item->alternative));
                            break;
                        }
                    }
                } else {
                    /* Complete: advance every item in the current state that is
                     * waiting (dot pointing at non-terminal) for $item->rule */
                    foreach ($items as $waiting) {
                        $wsymbol =
                            $waiting->alternative
                                ->symbols[$waiting->dot] ?? null;

                        if ($wsymbol === null) {
                            continue;
                        }

                        if ($wsymbol->name !== $item->rule) {
                            continue;
                        }

                        $add(new Item(
                            rule:        $waiting->rule,
                            alt:         $waiting->alt,
                            dot:         $waiting->dot + 1,
                            origin:      0,
                            backs:       [],
                            alternative: $waiting->alternative));
                    }
                }
            }

            \usort($items,
                fn(Item $a, Item $b) =>
                    $a->rule <=> $b->rule ?:
                    $a->alt  <=> $b->alt  ?:
                    $a->dot  <=> $b->dot);

            return $items;
        }
    }
}