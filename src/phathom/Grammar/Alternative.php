<?php
namespace pharos\phathom\Grammar {
    final class Alternative {
        private function __construct(
            public              array      $symbols,
            public private(set) int|false  $priority = false,
            public private(set) ?string    $action   = null,
            public private(set) Quantifier $synthetic = Quantifier::NONE,
        ) {}

        public static function complex(array $symbols, int|false $priority = false, ?string $action = null) : Alternative {
            return new self($symbols, $priority, $action);
        }

        public static function simple(Symbol $symbol) : Alternative {
            return new self([$symbol]);
        }

        public static function synthetic(array $symbols, Quantifier $quantifier) : Alternative {
            return new self($symbols, false, null, $quantifier);
        }
    }
}
?>