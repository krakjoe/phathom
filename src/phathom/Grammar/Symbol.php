<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    final class Symbol {
        public int|false $terminal = false;

        public function __construct(
            public private(set) int        $type,
            public private(set) string     $name,
            public private(set) array      $location   = [],
            public private(set) Quantifier $quantifier = QUANTIFIER::NONE,
        ) {}
    }
}
?>