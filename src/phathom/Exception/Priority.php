<?php declare(strict_types=1);

namespace pharos\phathom\Exception {
    use \pharos\phathom\File;

    final class Priority extends \pharos\phathom\Exception {
        public static function inconsistent(File $file, string $rule, int $alternative) : Priority {
            return new self(\sprintf(
                "Priority annotation inconsistent for ".
                "alternative %d at '%s' in %s",
                $alternative,
                $rule,
                $file));
        }

        public static function ambiguous(File $file, string $rule, int $alternative) : Priority {
            return new self(\sprintf(
                "Priority annotation ambiguous for ".
                "alternative %d at '%s' in %s",
                $alternative,
                $rule,
                $file));
        }

        public static function inert(File $file, string $rule) : Priority {
            return new self(\sprintf(
                "Priority annotation inert for ".
                "single alternative at '%s' in %s",
                $rule,
                $file));
        }
    }
}
?>