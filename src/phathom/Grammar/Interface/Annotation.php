<?php declare(strict_types=1);

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