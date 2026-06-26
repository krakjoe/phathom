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
    enum Quantifier {
        case NONE;
        case STAR;
        case PLUS;
        case OPTIONAL;

        public static function from(?string $quantifier) : Quantifier {
            return match($quantifier) {
                '?'     => Quantifier::OPTIONAL,
                '+'     => Quantifier::PLUS,
                '*'     => Quantifier::STAR,
                default => Quantifier::NONE
            };
        }

        public static function name(Quantifier $quantifier) : string {
            return match($quantifier) {
                Quantifier::NONE     => 'none',
                Quantifier::STAR     => 'star',
                Quantifier::PLUS     => 'plus',
                Quantifier::OPTIONAL => 'opt',
            };
        }
    }
}
?>