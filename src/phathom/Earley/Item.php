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

namespace pharos\phathom\Earley {
    use \pharos\phathom\Grammar\Alternative;

    final class Item {
        public int $pos;

        public function __construct(
            public private(set) string      $rule,
            public private(set) int         $alt,
            public private(set) int         $dot,
            public private(set) int         $origin,
            public              array       $backs,
            public private(set) Alternative $alternative) {}
    }
}
?>