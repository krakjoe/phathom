<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;

    final class Alternative {
        private function __construct(
            public private(set) File       $file,
            public              array      $symbols,
            public private(set) int|false  $priority = false,
            public private(set) ?string    $action   = null,
            public private(set) Quantifier $synthetic = Quantifier::NONE,
        ) {}

        public static function complex(File $file, array $symbols, int|false $priority = false, ?string $action = null) : Alternative {
            return new self($file, $symbols, $priority, $action);
        }

        public static function simple(File $file, Symbol $symbol) : Alternative {
            return new self($file, [$symbol]);
        }

        public static function synthetic(File $file, array $symbols, Quantifier $quantifier) : Alternative {
            return new self($file, $symbols, false, null, $quantifier);
        }
    }
}
?>