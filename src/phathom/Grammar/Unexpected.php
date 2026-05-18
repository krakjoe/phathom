<?php
namespace pharos\phathom\Grammar {
    final class Unexpected extends \Exception {

        private static function explain(array $options) : string {
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
                        $token['type'],
                        \substr($token['value'], 0, 32),
                        $token['location']['path'],
                        $token['location']['position']);
                }
                return \sprintf(
                    "%s(%s) at %s:%d",
                    $token['type'],
                    $token['value'],
                    $token['location']['path'],
                    $token['location']['position']);
            }

            return \sprintf(
                "%s at %s:%d",
                $token['type'],
                $token['location']['path'],
                $token['location']['position']);
        }

        public static function initial(array $token) : Unexpected {
            return new self(\sprintf(
                "Unexpected %s, initial token must be ".
                    "IDENT, " .
                "got %s",
                $token['type'],
                Unexpected::print($token)));
        }

        public static function token(array $token, array $next, array $rules) : Unexpected {
            return new self(\sprintf(
                "Unexpected %s, %s must be followed by %s, got %s",
                $next['type'],
                $token['type'],
                Unexpected::explain($rules),
                Unexpected::print($next)));
        }

        public static function unterminated(string $content, array $location, string $delimiter) : Unexpected {
            return new self(\sprintf(
                "Unexpected unterminated STRING, ".
                "STRING must be terminated by %s, ".
                "got STRING(%s) ".
                "starting at %s:%d",
                $delimiter,
                $content,
                $location['path'], $location['position']));
        }

        public static function unbalanced(string $content, array $location, array $delimiters) : Unexpected {
            return new self(\sprintf(
                "Unexpected unbalanced %s%s block, ".
                "missing %s ".
                "in \"%s\", ".
                "starting at %s:%d",
                $delimiters['open'], $delimiters['close'],
                $delimiters['close'],
                $content,
                $location['path'], $location['position']));
        }

        public static function character(string $buffer, array $location) : Unexpected {
            return new self(\sprintf(
                "Unexpected character \"%s\", ".
                "expected IDENT at %s:%d",
                $buffer[$location['position']],
                $location['path'], $location['position']));
        }

        public static function escape(array $location, array $delimiters) : Unexpected {
            return new self(\sprintf(
                "Unexpected escape in %s%s block, ".
                "expected more input at %s:%d",
                $delimiters['open'], $delimiters['close'],
                $location['path'], $location['position']));
        }
    }
}