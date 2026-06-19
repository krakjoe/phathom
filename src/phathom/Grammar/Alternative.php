<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;

    final class Alternative {
        public private(set) int|false     $priority      = false;
        public private(set) Associativity $associativity = Associativity::NONE;

        private function __construct(
            public private(set) File        $file,
            public              array       $symbols,
            public private(set) array|false $annotations = false,
            public private(set) ?string     $action      = null,
            public private(set) Quantifier  $synthetic   = Quantifier::NONE,
        ) {
            if ($annotations === false) {
                return;
            }

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Annotation\Priority) {
                    $this->priority = 
                        $annotation->value;
                }

                if ($annotation instanceof Annotation\Associativity) {
                    $this->associativity =
                        $annotation->value;
                }
            }
        }

        public static function complex(File $file, array $symbols, array|false $annotations = false, ?string $action = null) : Alternative {
            return new self($file, $symbols, $annotations, $action);
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