<?php declare(strict_types=1);

namespace pharos\phathom\Earley {
    use \pharos\phathom\Grammar\Quantifier;

    final class Frame {
        private const int SELECT = 1;
        private const int APPLY  = 2;

        private function __construct(
            private int          $kind,
            private Item         $item,
            private array|false  $partial = false,
            private array|false  $slots   = false,
        ) {}

        public static function select(Item $item) : Frame {
            return new self(Frame::SELECT, $item);
        }

        public static function apply(Item $item, array $partial, array $slots) : Frame {
            return new self(Frame::APPLY, $item, $partial, $slots);
        }

        public function __invoke(
            \Closure  $select,
            \Closure  $apply,
            array    &$stack,
            array    &$heap,
            array     $tokens,
        ): void {
            $alternative =
                $this->item
                    ->alternative;

            switch ($this->kind) {
                case Frame::SELECT:
                    if (empty($alternative->symbols)) {
                        $heap[] =
                            $alternative->synthetic !== Quantifier::NONE ?
                                [] : null;
                        return;
                    }

                    $limit   = \count($alternative->symbols);
                    $partial = \array_fill(0, $limit, null);
                    $slots   = [];
                    $items   = [];

                    $item = $this->item;
                    for ($pos = $limit - 1; $pos >= 0; $pos--) {
                        $back = $select($item->backs, $tokens);
                        if ($back->token !== null) {
                            $partial[$pos] =
                                $tokens[$back->token];
                        } else {
                            $slots[] = $pos;
                            $items[] = $back->child;
                        }
                        $item = $back->prev;
                    }

                    if (empty($slots)) {
                        $heap[] = $apply(
                            $this->item, $partial);
                        return;
                    }

                    $stack[] = Frame::apply(
                        $this->item, $partial, $slots);
                    foreach ($items as $nitem) {
                        $stack[] = Frame::select($nitem);
                    }
                break;

                case Frame::APPLY:
                    foreach ($this->slots as $pos) {
                        $this->partial[$pos] =
                            \array_pop($heap);
                    }
                    $heap[] = $apply($this->item, $this->partial);
                break;

                default: { /* unreachable */ }
            }
        }
    }
}
?>