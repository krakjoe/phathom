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

namespace pharos\phathom\GLR {
    use \pharos\phathom\File;
    use \pharos\phathom\Buffer;
    use \pharos\phathom\Grammar;

    /*
    * GLR parse chart.
    *
    * Maintains a frontier of active Thread objects.  Each iteration:
    *
    *   1. reduce  — apply all reductions to fixed point, forking on
    *                shift/reduce and reduce/reduce conflicts.
    *                Threads are deduplicated by (state-stack, top-node)
    *                to keep the frontier bounded.
    *
    *   2. scan    — ask the lexer for the next token, given the union
    *                of terminals expected across all top states.
    *
    *   3. shift   — advance every thread that can shift that token,
    *                deduplicating by state-stack.
    *
    * After the last token a final reduce is run; accepting threads
    * (those whose top state == Automaton::$accept) are exposed via
    * $this->threads.
    */
    final class Chart {
        private const string RESERVE = "\$Chart\$";

        private Automaton $automaton;

        public private(set) string $start;
        public private(set) array  $tokens  = [];  /* Token[]              */
        public private(set) int    $limit   = 0;   /* count($tokens)       */
        public private(set) array  $threads = [];  /* Thread[] — accepting */

        public function __construct(
            Grammar           $grammar,
            File|Buffer       $input,
        ) {
            [
                $this->automaton,
                $this->start,
                $scanner,
                $class,
                $literals,
            ] = $this->start($grammar);

            $threads = [
                new Thread(
                    [Automaton::INITIAL],
                    [null], 0)];

            while (true) {
                $threads  = $this->reduce($threads);
                $expected = $this->expected($threads);

                if (!$expected) {
                    break;
                }

                $token = $scanner(
                    $input, $this->limit,
                    $expected, $class, $literals);

                if ($token === null) {
                    break;
                }

                $ti = \count($this->tokens);
                $this->tokens[] = $token;

                $threads = $this->shift(
                    $threads, $token->type, $ti);
            }

            $scanner(
                $input, $this->limit,
                [], $class, $literals);

            $this->threads = \array_values(\array_filter(
                $threads,
                fn(Thread $t) => $t->top() === $this->automaton->accept));
            $this->limit   = \count($this->tokens);
        }

        private function start(Grammar $grammar) : array {
            $this->start = $grammar->start;

            return [
                $grammar->engine->automaton,
                $grammar->start,
                $grammar->lexer->scan(...),
                $grammar->token,
                $grammar->literals,
            ];
        }

        private function expected(array $threads) : array {
            $expected = [];
            foreach ($threads as $thread) {
                foreach ($this->automaton->shifts[$thread->top()] ?? [] as $tid => $_) {
                    $expected[$tid] = true;
                }
            }
            return $expected;
        }

        private function shift(array $threads, int $type, int $ti) : array {
            $shifted = [];
            $seen    = [];

            foreach ($threads as $thread) {
                $next = $this->automaton->shifts[$thread->top()][$type] ?? null;
                if ($next === null) {
                    continue;
                }
                $new  = $thread->shift($next, $ti);
                $slot = &$seen;
                foreach ($new->states as $s) { $slot = &$slot[$s]; }
                if (!isset($slot[Chart::RESERVE])) {
                    $slot[
                        Chart::RESERVE] = true;
                    $shifted[] = $new;
                }
            }

            return $shifted;
        }

        /*
        * Apply all reductions to fixed point, with local ambiguity resolution.
        *
        * When two threads produce nodes for the same (base_states, goto, rule, pos)
        * — meaning they are competing derivations of the same rule at the same span
        * — priority and associativity annotations select the winner immediately.
        * The losing node is not propagated, so at most one parse tree survives each
        * local ambiguity point.  True ambiguities surface as Ambiguity exceptions at
        * the root level in Evaluator::__invoke.
        *
        * Deduplication uses trie-indexed nested arrays throughout: state-IDs are
        * integers so each level is an O(1) integer hash lookup, and shared state
        * prefixes are traversed only once.  A string sentinel (RESERVE) stores
        * the payload one level deeper than the last state, preventing prefix
        * collisions when one thread's state path is a prefix of another's.
        */
        private function reduce(array $threads) : array {
            $worklist  = $threads;
            $result    = [];
            $best_node = [];   /* trie: base_states[RESERVE][goto][rule][pos] => Node */
            $node_key  = [];   /* spl_obj_id => [base_states, goto, rule, pos]       */

            while ($thread = \array_shift($worklist)) {
                $top = $thread->nodes[\count($thread->nodes) - 1];

                if ($top instanceof Node) {
                    $path = $node_key[\spl_object_id($top)] ?? null;
                    if ($path !== null) {
                        [$bstates, $bgoto, $brule, $bpos] = $path;
                        $bn = &$best_node;
                        foreach ($bstates as $s) { $bn = &$bn[$s]; }
                        if ($bn[Chart::RESERVE][$bgoto][$brule][$bpos] !== $top) {
                            continue;
                        }
                    }
                }

                $result[] = $thread;

                foreach ($this->automaton->reduces[$thread->top()] ?? [] as $reduction) {
                    [$base, $children] = $thread->pop($reduction->length);

                    $goto = $this->automaton->goto[$base->top()][$reduction->rule];

                    $node = new Node(
                        $reduction->rule,
                        $reduction->alt,
                        $reduction->alternative,
                        $children,
                        $thread->pos);

                    $bn = &$best_node;
                    foreach ($base->states as $s) { $bn = &$bn[$s]; }

                    if (isset($bn[Chart::RESERVE][$goto][$reduction->rule][$thread->pos])) {
                        $prev   = $bn[Chart::RESERVE][$goto][$reduction->rule][$thread->pos];
                        $winner = $this->resolve($prev, $node);
                        if ($winner === $prev) {
                            continue;
                        }
                        if ($winner === null) {
                            /* Unresolvable — propagate both so root detects ambiguity. */
                            $worklist[] = $base->push($goto, $node);
                            continue;
                        }
                        $node_key[\spl_object_id($node)] =
                            [$base->states, $goto, $reduction->rule, $thread->pos];
                        $bn[Chart::RESERVE][$goto][$reduction->rule][$thread->pos] = $node;
                    } else {
                        $node_key[\spl_object_id($node)] =
                            [$base->states, $goto, $reduction->rule, $thread->pos];
                        $bn[Chart::RESERVE][$goto][$reduction->rule][$thread->pos] = $node;
                    }

                    $worklist[] = $base->push($goto, $node);
                }
            }

            return $result;
        }

        private function resolve(Node $a, Node $b) : ?Node {
            $pri_a = $a->alternative->priority;
            $pri_b = $b->alternative->priority;

            if ($pri_a === false || $pri_b === false) {
                return null; /* unresolvable — let both propagate to surface ambiguity */
            }
            if ($pri_a !== $pri_b) {
                return $pri_a > $pri_b ? $a : $b;
            }

            if ($a->alternative->associativity ===
                    \pharos\phathom\Grammar\Associativity::LEFT) {
                return $a->leftEnd() >= $b->leftEnd() ? $a : $b;
            }
            return $a->leftEnd() <= $b->leftEnd() ? $a : $b;
        }
    }
}
?>
