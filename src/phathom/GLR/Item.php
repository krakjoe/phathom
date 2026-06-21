<?php declare(strict_types=1);

namespace pharos\phathom\GLR {
    use \pharos\phathom\Grammar\Alternative;

    final class Item {
        public private(set) string $key;

        public function __construct(
            public private(set) string      $rule,
            public private(set) int         $alt,
            public private(set) int         $dot,
            public private(set) int         $length,
            public private(set) Alternative $alternative,
        ) {
            $this->key = "\${$rule}\x00{$alt}\x00{$dot}\$";
        }
    }
}
?>
