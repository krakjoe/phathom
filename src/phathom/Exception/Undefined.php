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

        public static function variable(File $file, string $rule, int $alternative, int $variable) : Undefined {
            return new self(\sprintf(
                "Undefined variable '$%d' in alternative %d for '%s' in %s",
                $variable,
                $alternative,
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