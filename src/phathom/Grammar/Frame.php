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

namespace pharos\phathom\Grammar {
    use \pharos\phathom\Grammar\Quantifier;
    use \pharos\phathom\Grammar\Interface;

    final class Frame implements Interface\Frame {
        public function __construct(
            private int          $kind,
            private mixed        $selected,
            private array|false  $partial = false,
            private array|false  $slots   = false,
        ) {}

        public static function select(mixed $selected) : static {
            return new self(Frame::SELECT, $selected);
        }

        public static function apply(mixed $selected, array $partial, array $slots) : static {
            return new self(Frame::APPLY, $selected, $partial, $slots);
        }

        public function __invoke(
            \Closure  $children,
            \Closure  $apply,
            array    &$stack,
            array    &$heap,
            array     $tokens,
        ) : void {
            switch ($this->kind) {
                case Frame::SELECT:
                    if (empty($this->selected->alternative->symbols)) {
                        $heap[] =
                            $this->selected->alternative->synthetic !== Quantifier::NONE
                                ? [] : null;
                        return;
                    }

                    [
                        $partial,
                        $slots,
                        $nodes
                    ] = $children($this->selected, $tokens);

                    if (empty($slots)) {
                        $heap[] = $apply(
                            $this->selected, $partial);
                        return;
                    }

                    $stack[] = Frame::apply(
                        $this->selected, $partial, $slots);
                    foreach ($nodes as $node) {
                        $stack[] = Frame::select($node);
                    }
                break;

                case Frame::APPLY:
                    foreach ($this->slots as $pos) {
                        $this->partial[$pos] =
                            \array_pop($heap);
                    }
                    $heap[] = $apply(
                        $this->selected, $this->partial);
                break;
            }
        }
    }
}
?>
