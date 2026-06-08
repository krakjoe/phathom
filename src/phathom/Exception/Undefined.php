<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\File;

    final class Undefined extends \pharos\phathom\Exception {
        public static function rules(File $file) : Undefined {
            return new self(\sprintf(
                "Undefined rules, grammar must contain at least one rule in %s",
                $file));
        }

        public static function symbol(File $file, string $rule, string $symbol) : Undefined {
            return new self(\sprintf(
                "Undefined symbol '%s' at '%s' in %s",
                $symbol,
                $rule,
                $file
            ));
        }

        public static function start(File $file, string $rule) : Undefined {
            return new self(\sprintf(
                "Undefined start rule '%s' in %s",
                $rule,
                $file
            ));
        }
    }
}
?>