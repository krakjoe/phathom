<?php declare(strict_types=1);

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