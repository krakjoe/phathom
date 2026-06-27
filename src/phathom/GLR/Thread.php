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
    /*
    * Immutable parser thread: a stack of LR state-IDs alongside a parallel
    * stack of semantic values (int = token index, Node = sub-tree, null =
    * stack-bottom sentinel).
    *
    * pos tracks how many tokens this thread has consumed; it advances only
    * on shift, never on reduce.
    *
    * Threads are deduplicated in Chart::reduce using a trie indexed by
    * $states to keep the forest bounded.
    */
    final class Thread {
        public function __construct(
            public private(set) array $states,  /* int[]              */
            public private(set) array $nodes,   /* (int|Node|null)[]  */
            public private(set) int   $pos,     /* tokens consumed    */
        ) {}

        public function top() : int {
            return $this->states[\count($this->states) - 1];
        }

        /* Shift: consume token at index $ti, advance to state $state. */
        public function shift(int $state, int $ti) : Thread {
            return new Thread(
                [...$this->states, $state],
                [...$this->nodes,  $ti],
                $this->pos + 1);
        }

        /* Push a Node (after reduction). Does NOT advance pos. */
        public function push(int $state, Node $node) : Thread {
            return new Thread(
                [...$this->states, $state],
                [...$this->nodes,  $node],
                $this->pos);
        }

        /*
        * Pop $count entries from both stacks.
        * Returns [$reduced_thread, $popped_nodes].
        */
        public function pop(int $count) : array {
            if ($count === 0) {
                return [$this, []];
            }
            $len    = \count($this->states);
            $popped = \array_slice($this->nodes, $len - $count, $count);
            return [
                new Thread(
                    \array_slice($this->states, 0, $len - $count),
                    \array_slice($this->nodes,  0, $len - $count),
                    $this->pos),
                $popped,
            ];
        }
    }
}
?>
