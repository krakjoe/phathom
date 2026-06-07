<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\Token;
    use \pharos\phathom\File;

    final class Directive extends \pharos\phathom\Exception {
        public static function unknown(Token $token, array $allowed) : Directive {
            return new self(\sprintf(
                "Unknown directive, expected %s, ".
                "got %s",
                self::explain($allowed),
                $token::print($token)));
        }

        public static function abstract(Token $directive, array $directives) : Directive {
            return new self(\sprintf(
                "%s already declared as \"%s\" at %s:%d",
                (string) $directive,
                (string) $directives[(string) $directive],
                $directives[(string) $directive]
                    ->location['path'],
                $directives[(string) $directive]
                    ->location['position']
            ));
        }

        public static function include(Token $token, string $path, array $duplicate) : Directive {
            return new self(\sprintf(
                "include for %s at %s:%d, ".
                    "already included at %s:%d ",
                $path, $token->location['path'], $token->location['position'],
                    $duplicate['path'], $duplicate['position']));
        }

        public static function lexer(Token $location, Token $duplicate) : Directive {
            return new self(\sprintf(
                "%s already loaded at %s:%d",
                (string) $location,
                $duplicate->location['path'],
                $duplicate->location['position']
            ));
        }

        public static function missing(Token $location, Token $directive) : Directive {
            return new self(\sprintf(
                "%s cannot be found on the local filesystem at %s:%d",
                (string) $location,
                $directive->location['path'],
                $directive->location['position']
            ));
        }

        public static function reserved(Token $ident, array $reserved) : Directive {
            return new self(\sprintf(
                "%s cannot be used as a rule name; ".
                    "%s are reserved for directives, ".
                "got %s",
                (string) $ident,
                self::explain(
                    $reserved, 'and'),
                $ident::print($ident)
            ));
        }
    }
}
?>