<?php declare(strict_types=1);
/*
  +----------------------------------------------------------------------+
  | phathom                                                              |
  +----------------------------------------------------------------------+
  | Copyright (c) Joe Watkins 2026                                       |
  +----------------------------------------------------------------------+
  | This source file is subject to the BSD 3-Clause License bundled     |
  | with this package in the file LICENSE.                               |
  +----------------------------------------------------------------------+
  | Author: krakjoe                                                      |
  +----------------------------------------------------------------------+
 */

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