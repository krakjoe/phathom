<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\File;

    final class Undefined extends \pharos\phathom\Exception {
        public static function symbol(File $file, string $rule, string $symbol) : Undefined {
            return new self(\sprintf(
                "Undefined symbol '%s' at '%s' in %s",
                $symbol,
                $rule,
                $file
            ));
        }
    }
}
?>