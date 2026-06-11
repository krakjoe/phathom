<?php declare(strict_types=1);

namespace pharos\phathom
{
    abstract class Token {
        public function __construct(
            public private(set) int    $type,
            public private(set) array  $location,
            public private(set) mixed  $value = null,
        ) {}

        public abstract static function string(int $type) : string;

        public static function print(Token $token) : string {
            if (isset($token->value)) {
                if (\strlen($token->value) > 32) {
                    return \sprintf(
                        "%s(%s...) at %s:%d",
                        static::string($token->type),
                        \substr($token->value, 0, 32),
                        $token->location['path'],
                        $token->location['position']);
                }
                return \sprintf(
                    "%s(%s) at %s:%d",
                    static::string($token->type),
                    $token->value,
                    $token->location['path'],
                    $token->location['position']);
            }

            return \sprintf(
                "%s at %s:%d",
                static::string($token->type),
                $token->location['path'],
                $token->location['position']);
        }

        final public function __toString() : string {
            return (string) $this->value;
        }
    }
}
?>