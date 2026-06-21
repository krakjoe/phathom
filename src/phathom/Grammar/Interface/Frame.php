<?php declare(strict_types=1);

namespace pharos\phathom\Grammar\Interface {
    interface Frame {
        public const int SELECT = 1;
        public const int APPLY  = 2;

        public function __construct(
            int          $kind,
            mixed        $selected,
            array|false  $partial = false,
            array|false  $slots   = false,
        );

        public static function select(mixed $selected) : static;
        public static function apply(mixed $selected, array $partial, array $slots) : static;

        public function __invoke(
            \Closure  $children,
            \Closure  $apply,
            array    &$stack,
            array    &$heap,
            array     $tokens,
        ) : void;
    }
}