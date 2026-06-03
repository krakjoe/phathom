<?php
namespace pharos\phathom\Earley {
    final class Back {
        public function __construct(
            public \WeakReference  $prev,
            public ?\WeakReference $child,
            public ?int            $token) {}
    }
}
?>
