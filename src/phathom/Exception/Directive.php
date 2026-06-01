<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\Token;
    use \pharos\phathom\File;

    final class Directive extends \pharos\phathom\Exception {
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
    }
}
?>