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
    enum Associativity {
        case NONE;
        case LEFT;
        case RIGHT;

        public static function from(?string $value) : Associativity {
            return match(\strtolower((string) $value)) {
                'left'  => Associativity::LEFT,
                'right' => Associativity::RIGHT,
                default => Associativity::NONE,
            };
        }
    }
}
?>