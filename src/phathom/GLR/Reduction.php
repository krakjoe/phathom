<?php declare(strict_types=1);

namespace pharos\phathom\GLR {
    use \pharos\phathom\Grammar\Alternative;

    final class Reduction {
        public function __construct(
            public private(set) string      $rule,
            public private(set) int         $alt,
            public private(set) int         $length,
            public private(set) Alternative $alternative,
        ) {}
    }
}
?>
