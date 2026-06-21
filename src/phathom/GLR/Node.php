<?php declare(strict_types=1);

namespace pharos\phathom\GLR {
    use \pharos\phathom\Grammar\Alternative;

    /*
    * Explicit parse tree node built during GLR reduction.
    *
    * Children are either an int (token index into Chart::$tokens)
    * or a Node (sub-tree from a prior reduction).
    *
    * start/end are token-index spans: start is the index of the first
    * token consumed, end is one past the last (exclusive).  They are
    * used by the Engine to resolve priority ties by associativity.
    */
    final class Node {
        public private(set) int $start;
        public private(set) int $end;

        public function __construct(
            public private(set) string      $rule,
            public private(set) int         $alt,
            public private(set) Alternative $alternative,
            public private(set) array       $children,  /* (int|Node)[] */
            int                         $pos,       /* thread->pos at reduction = end */
        ) {
            $this->end   = $pos;
            $this->start = $this->computeStart($children, $pos);
        }

        /*
        * For associativity resolution: the "leftEnd" is the exclusive end
        * of the first non-terminal child.  A larger leftEnd means the left
        * sub-tree consumed more tokens — the left-associative grouping.
        */
        public function leftEnd() : int {
            foreach ($this->children as $child) {
                if ($child instanceof Node) {
                    return $child->end;
                }
            }
            return $this->end;
        }

        private function computeStart(array $children, int $default) : int {
            foreach ($children as $child) {
                if ($child instanceof Node) {
                    return $child->start;
                }
                if (\is_int($child)) {
                    return $child; /* token index IS the start position */
                }
            }
            return $default;
        }
    }
}
?>
