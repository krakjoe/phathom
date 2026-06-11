<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    enum Quantifier {
        case NONE;
        case STAR;
        case PLUS;
        case OPTIONAL;

        public static function from(?string $quantifier) : Quantifier {
            return match($quantifier) {
                '?'     => QUANTIFIER::OPTIONAL,
                '+'     => QUANTIFIER::PLUS,
                '*'     => QUANTIFIER::STAR,
                default => QUANTIFIER::NONE
            };
        }

        public static function name(Quantifier $quantifier) : string {
            return match($quantifier) {
                QUANTIFIER::NONE     => 'none',
                QUANTIFIER::STAR     => 'star',
                QUANTIFIER::PLUS     => 'plus',
                QUANTIFIER::OPTIONAL => 'opt',
            };
        }
    }
}
?>