<?php
namespace pharos\phathom\Earley {
    use \pharos\phathom\Lexer;

    final class Optimizer {
        const string VISITED = '@';

        public function __construct(
            private Lexer  $lexer,
            private string $start,
            private array  $rules) {
            /*
            * We're going to prewarm the pattern cache on lexer by telling it what it may expect
            * at any call to scan for this grammar, this means that no strings are allocated for patterns
            * during parse time, and all patterns are serialized with the grammar.
            */
            $optimize = $this
                ->lexer
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
                $initial[] = [$this->start, $aid, 0];
            }

            $visited = [];
            $queue   = [$this->collect($initial)];

            while ($queue) {
                $state = \array_pop($queue);

                /* Canonical state key: items are already sorted by close();
                 * walk the trie to detect revisited states without building strings */
                $node = &$visited;
                foreach ($state as [$rule, $alt, $dot]) {
                    $node = &$node[$rule][$alt][$dot];
                }

                if (isset($node[Optimizer::VISITED])) {
                    continue;
                }

                $node[Optimizer::VISITED] = true;

                /* Collect expected terminals and group seeds for scan successors */
                $expected   = [];
                $successors = [];

                foreach ($state as [$rule, $alt, $dot]) {
                    $symbol =
                        $this->rules[$rule][$alt]
                            ->symbols[$dot] ?? null;
                    if ($symbol === null) {
                        continue;
                    }

                    if (($type = $symbol->terminal) === false) {
                        continue;
                    }

                    $expected[$type] = true;
                    $successors[$type][] = 
                        [$rule, $alt, $dot + 1];
                }

                if ($expected) {
                    $optimize($expected);
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

            $add = function(string $rule, int $alt, int $dot)
                        use (&$seen, &$items, &$worklist) : void {
                $slot = &$seen[$rule][$alt][$dot];
                if (!isset($slot)) {
                    $item       = [$rule, $alt, $dot];
                    $worklist[] =
                        $items[] =
                            $slot = $item;
                }
            };

            foreach ($seeds as [$rule, $alt, $dot]) {
                $add($rule, $alt, $dot);
            }

            while ($worklist) {
                [$rule, $alt, $dot] =
                    \array_pop($worklist);

                $symbols =
                    $this->rules
                        [$rule]
                        [$alt]
                            ->symbols;

                if ($dot < \count($symbols)) {
                    $symbol = $symbols[$dot];

                    if ($symbol->terminal !== false) {
                        continue; /* nothing to predict or advance */
                    }

                    /* Predict: add all alternatives of $symbol->name at dot=0 */
                    foreach ($this->rules[
                                $symbol->name] as 
                                    $aid => $alternative) {
                        $add($symbol->name, $aid, 0);
                    }

                    /* Advance past already-completed $symbol->name: if any completed item
                     * for $symbol->name is already in the state, the current item can
                     * immediately advance past it (handles ε-productions and the
                     * left-recursive synthetic rules produced by quantifiers) */
                    foreach ($this->rules[
                                $symbol->name] as 
                                    $aid => $alternative) {
                        $alen = \count($alternative->symbols);
                        if (isset($seen[$symbol->name][$aid][$alen])) {
                            $add($rule, $alt, $dot + 1);
                            break;
                        }
                    }
                } else {
                    /* Complete: advance every item in the current state that is
                     * waiting (dot pointing at non-terminal) for $rule */
                    foreach ($items as [$wrule, $walt, $wdot]) {
                        $wsymbol =
                            $this->rules[$wrule][$walt]
                                ->symbols[$wdot] ?? null;
                        if ($wsymbol !== null       &&
                            $wsymbol->terminal === false &&
                            $wsymbol->name     === $rule) {
                            $add($wrule, $walt, $wdot + 1);
                        }
                    }
                }
            }

            \usort($items,
                fn($a, $b) =>
                    $a[0] <=> $b[0] ?:
                    $a[1] <=> $b[1] ?:
                    $a[2] <=> $b[2]);

            return $items;
        }
    }
}
?>