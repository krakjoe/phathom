<?php
namespace pharos\phathom\Earley {
    final class Back {
        public function __construct(
            public Item  $prev,
            public ?Item $child,
            public ?int  $token) {}
    }
}
?>
