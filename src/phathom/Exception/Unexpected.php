<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\Grammar\Token;

    final class Unexpected extends \pharos\phathom\Exception {

        private static function explain(array $options) : string {
            $options = \array_map(function($option) {
                if (\is_int($option)) {
                    return Token::string($option);
                }
                return $option;
            }, $options);

            switch (\count($options)) {
                case 1:
                    return $options[0];
                
                case 2:
                    return \vsprintf("%s or %s", $options);

                default:
                    $last =
                        \array_pop($options);
                    return \sprintf(
                        "%s, or %s",
                        \implode(", ", $options),
                        $last);
            }
        }

        private static function print(array $token) : string {
            if (isset($token['value'])) {
                if (\strlen($token['value']) > 32) {
                    return \sprintf(
                        "%s(%s...) at %s:%d",
                        Token::string($token['type']),
                        \substr($token['value'], 0, 32),
                        $token['location']['path'],
                        $token['location']['position']);
                }
                return \sprintf(
                    "%s(%s) at %s:%d",
                    Token::string($token['type']),
                    $token['value'],
                    $token['location']['path'],
                    $token['location']['position']);
            }

            return \sprintf(
                "%s at %s:%d",
                Token::string($token['type']),
                $token['location']['path'],
                $token['location']['position']);
        }

        public static function directive(array $token, array $allowed) : Unexpected {
            return new self(\sprintf(
                "Unexpected directive, expected %s, ".
                "got %s",
                Unexpected::explain($allowed),
                Unexpected::print($token)));
        }

        public static function include(array $token, string $path, array $location) : Unexpected {
            return new self(\sprintf(
                "Unexpected duplicate include at %s:%d, ".
                "%s already included at %s:%d ",
                $token['location']['path'], $token['location']['position'],
                $path, $location['path'], $location['position']));
        }

        public static function initial(array $token) : Unexpected {
            return new self(\sprintf(
                "Unexpected %s, initial token must be ".
                    "IDENT, " .
                "got %s",
                Token::string($token['type']),
                Unexpected::print($token)));
        }

        public static function token(array $token, array $next, array $rules) : Unexpected {
            return new self(\sprintf(
                "Unexpected %s, %s must be followed by %s, got %s",
                Token::string($next['type']),
                Token::string($token['type']),
                Unexpected::explain($rules),
                Unexpected::print($next)));
        }

        public static function nondigit(int $type, string $content, array $location) : Unexpected {
            return new self(\sprintf(
                "Unexpected non-digit in %s, " . 
                "%s may only contain digits, " .
                "got %s ".
                "starting at %s:%d",
                Token::string($type),
                Token::string($type),
                $content,
                $location['path'], $location['position']));
        }

        public static function empty(int $type, array $location, array $delimiters) : Unexpected {
            return new self(\sprintf(
                "Unexpected empty %s, ".
                "%s must contain content between %s and %s, ".
                "starting at %s:%d",
                Token::string($type),
                Token::string($type), $delimiters['open'], $delimiters['close'],
                $location['path'], $location['position']));
        }

        public static function unterminated(int $type, string $content, array $location, array $delimiters) : Unexpected {
            return new self(\sprintf(
                "Unexpected unterminated %s, ".
                "%s started with %s must be terminated by %s, ".
                "got %s(%s) ".
                "starting at %s:%d",
                Token::string($type),
                Token::string($type), $delimiters['open'], $delimiters['close'],
                Token::string($type), $content,
                $location['path'], $location['position']));
        }

        public static function unbalanced(int $type, string $content, array $location, array $delimiters) : Unexpected {
            return new self(\sprintf(
                "Unexpected unbalanced %s, ".
                "%s started with %s and terminated by %s, ".
                    "may contain an unescaped %s, or be missing %s, " .
                "got %s(%s), ".
                "starting at %s:%d",
                Token::string($type),
                Token::string($type), $delimiters['open'], $delimiters['close'],
                $delimiters['open'], $delimiters['close'],
                Token::string($type), $content,
                $location['path'], $location['position']));
        }

        public static function character(string $buffer, array $location, array $rules) : Unexpected {
            return new self(\sprintf(
                "Unexpected character \"%s\", ".
                "expected %s at %s:%d",
                $buffer[$location['position']],
                Unexpected::explain($rules),
                $location['path'], $location['position']));
        }

        public static function escape(int $type, array $location, array $delimiters) : Unexpected {
            return new self(\sprintf(
                "Unexpected escape in %s, ".
                "%s started with %s and terminated by %s, ".
                    "must not end with an escape, " .
                "expected more input at %s:%d",
                Token::string($type),
                Token::string($type), $delimiters['open'], $delimiters['close'],
                $location['path'], $location['position']));
        }
    }
}