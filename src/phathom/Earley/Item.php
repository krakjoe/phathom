<?php declare(strict_types=1);

namespace pharos\phathom\Earley {
    use \pharos\phathom\Grammar\Alternative;

    final class Item {
        public int $pos;

        public function __construct(
            public private(set) string      $rule,
            public private(set) int         $alt,
            public private(set) int         $dot,
            public private(set) int         $origin,
            public              array       $backs,
            public private(set) Alternative $alternative) {}
    }
}
?>