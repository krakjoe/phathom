<?php
namespace pharos\phathom\Earley {
    final class Back {
        public function __construct(
            public int  $prev,
            public ?int $child,
            public ?int $token) {}
    }
}
?>
