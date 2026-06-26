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

namespace pharos\phathom\Exception {
    use \pharos\phathom\Grammar\Token;

    final class Unexpected extends \pharos\phathom\Exception\Lexer {
        public static function initial(Token $token) : Unexpected {
            return new self(\sprintf(
                "Unexpected %s, initial token must be ".
                    "IDENT, " .
                "got %s",
                Token::string($token->type),
                Token::print($token)));
        }

        public static function token(Token $token, Token $next, array $rules) : Unexpected {
            return new self(\sprintf(
                "Unexpected %s, %s must be followed by %s, got %s",
                Token::string($next->type),
                Token::string($token->type),
                self::explain($rules),
                Token::print($next)));
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

        public static function character(string $buffer, array $location, array $rules) : Unexpected {
            return new self(\sprintf(
                "Unexpected character \"%s\", ".
                "expected %s at %s:%d",
                $buffer[$location['position']],
                self::explain($rules),
                $location['path'], $location['position']));
        }

        public static function annotation(string $buffer, array $location) : Unexpected {
            return new self(\sprintf(
                "Unexpected annotation \"%s\", ".
                    "expected [0-9a-zA-Z]+ ".
                "at %s:%d",
                $buffer, $location['path'], $location['position']));
        }
    }
}
?>