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

namespace pharos\phathom\Grammar {
    final class Symbol {
        public int|false $terminal = false;

        public function __construct(
            public private(set) int        $type,
            public private(set) string     $name,
            public private(set) array      $location   = [],
            public private(set) Quantifier $quantifier = QUANTIFIER::NONE,
        ) {}
    }
}
?>