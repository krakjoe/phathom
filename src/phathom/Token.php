<?php
namespace pharos\phathom
{
    abstract class Token {
        public function __construct(
            public private(set) int    $type,
            public private(set) array  $location,
            public private(set) mixed  $value = null,
        ) {}

        public abstract static function string(int $type) : string;

        public function __toString() : string {
            return (string) $this->value;
        }
    }
}
?>