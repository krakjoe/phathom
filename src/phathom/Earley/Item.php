<?php
namespace pharos\phathom\Earley {
    final class Item {
        public int $pos;

        public function __construct(
            public string    $rule,
            public int       $alt,
            public int       $dot,
            public int       $origin,
            public int|false $priority,
            public array     $backs) {}
    }
}
?>