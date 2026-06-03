<?php
namespace pharos\phathom\Earley {
    use \WeakReference;

    final class Back {
        private WeakReference  $__prev__;
        private ?WeakReference $__child__;

        public private(set) Item $prev {
            get {
                return $this
                    ->__prev__->get();
            }
            set (Item $item) {
                $this->__prev__ =
                    WeakReference::create($item);
            }
        }

        public private(set) ?Item $child {
            get {
                return $this
                    ->__child__?->get();
            }
            set (?Item $item) {
                if ($item === null) {
                    $this->__child__ =
                        null;
                    return;
                }

                $this->__child__ =
                    WeakReference::create($item);
            }
        }

        public function __construct(
            Item  $prev,
            ?Item $child,
            public ?int $token) {
            $this->prev  = $prev;
            $this->child = $child;
        }
    }
}

