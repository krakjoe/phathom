<?php
namespace pharos\phathom\Earley {
    use \WeakReference;

    final class Back {
        /*
        * The normal object destructor (not the GC) can stack overflow due to deep recursion.
        *
        * Weak referencing breaks the cycle that causes that overflow.
        *
        * !DON'T EAT (OR CHANGE) THIS
        *   [DOGFOOD](https://github.com/php/php-src/commit/6529d7acd9912a609924633a43e6562799566225)!
        */
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

