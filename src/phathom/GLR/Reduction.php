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
