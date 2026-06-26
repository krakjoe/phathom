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

    final class Associativity extends \pharos\phathom\Exception {
        public static function inconsistent(File $file, string $rule, int $alternative) : Associativity {
            return new self(\sprintf(
                "Associativity annotation inconsistent for ".
                "alternative %d at '%s' in %s",
                $alternative,
                $rule,
                $file));
        }

        public static function ambiguous(File $file, string $rule, int $alternative, string $reason) : static {
            return new self(\sprintf(
                "Associativity annotation ambiguous (%s) for ".
                "alternative %d at '%s' in %s",
                $reason,
                $alternative,
                $rule,
                $file));
        }

        public static function inert(File $file, string $rule) : Associativity {
            return new self(\sprintf(
                "Associativity annotation inert for ".
                "single alternative at '%s' in %s",
                $rule,
                $file));
        }
    }
}
?>