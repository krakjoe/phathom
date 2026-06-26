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

namespace pharos\phathom\Grammar\Interface {
    interface Annotation {
        public mixed $value { get; }

        public static function name()   : string;
        public static function expect() : string;
        public static function match(
            \pharos\phathom\Grammar\Token $token) : bool;
    }
}
?>