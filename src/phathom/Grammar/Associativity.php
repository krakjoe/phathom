<?php declare(strict_types=1);

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