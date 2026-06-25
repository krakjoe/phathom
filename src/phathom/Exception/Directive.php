<?php declare(strict_types=1);

namespace pharos\phathom\Exception {
    use \pharos\phathom\Token;
    use \pharos\phathom\File;

    final class Directive extends \pharos\phathom\Exception {
        public static function unknown(Token $directive, array $allowed) : Directive {
            return new self(\sprintf(
                "Unknown directive, expected %s, ".
                "got %s",
                self::explain($allowed),
                $directive::print($directive)));
        }

        public static function abstract(string $kind, Token $directive, Token $declared) : Directive {
            return new self(\sprintf(
                "%s cannot be declared ".
                    "as \"%s\" at %s:%d, ".
                "%s already declared ".
                    "as \"%s\" at %s:%d, " .
                "and \"%s\" does not extend \"%s\"",
                $kind, (string) $directive,
                    $directive->location['path'], $directive->location['position'],
                $kind, (string) $declared,
                    $declared->location['path'], $declared->location['position'],
                (string) $directive, (string) $declared,
            ));
        }

        public static function optimizer(Token $directive, Token|false $declared) : Directive {
            return new self(\sprintf(
                "optimizer \"%s\" cannot be added ".
                    "at %s:%d, ".
                "already added ".
                    "%s",
                (string) $directive,
                    $directive->location['path'], $directive->location['position'],
                    $declared === false ?
                        \sprintf("(builtin)") :
                        \sprintf("at %s:%d",
                            $declared->location['path'],
                            $declared->location['position']),
            ));
        }

        public static function autoload(string $kind, Token $directive) : Directive {
            return new self(\sprintf(
                "cannot find %s for %s, ".
                    "it must be autoloadable ".
                "at %s:%d",
                (string) $directive, $kind,
                $directive->location['path'],
                $directive->location['position']));
        }

        public static function parent(string $kind, string $required, Token $directive) : Directive {
            return new self(\sprintf(
                "%s must extend %s, %s does not ".
                "at %s:%d",
                $kind, $required, (string) $directive,
                $directive->location['path'],
                $directive->location['position']));
        }

        public static function interface(string $kind, string $required, Token $directive) : Directive {
            return new self(\sprintf(
                "%s must implement %s, %s does not ".
                "at %s:%d",
                $kind, $required, (string) $directive,
                $directive->location['path'],
                $directive->location['position']));
        }

        public static function include(Token $directive, array $duplicate) : Directive {
            return new self(\sprintf(
                "include for %s at %s:%d, ".
                    "already included at %s:%d ",
                (string) $directive,
                    $directive->location['path'],
                    $directive->location['position'],
                $duplicate['path'], $duplicate['position']));
        }

        public static function lexer(Token $directive, Token $duplicate) : Directive {
            return new self(\sprintf(
                "%s cannot be loaded at %s:%d, ".
                    "already loaded at %s:%d",
                (string) $directive,
                    $directive->location['path'],
                    $directive->location['position'],
                $duplicate->location['path'],
                $duplicate->location['position']
            ));
        }

        public static function missing(Token $directive) : Directive {
            return new self(\sprintf(
                "%s cannot be found on the local filesystem at %s:%d",
                (string) $directive,
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

        public static function declared(string $name, Token $directive, Token $declared) : Directive {
            return new self(\sprintf(
                "%s cannot be declared ".
                    "as \"%s\" at %s:%d, ".
                "%s already declared ".
                    "as \"%s\" at %s:%d",
                $name, (string) $directive,
                $directive->location['path'], $directive->location['position'],
                $name, (string) $declared,
                $declared->location['path'], $declared->location['position']
            ));
        }

        public static function collector(Token $directive, array $policies) : Directive {
            return new self(\sprintf(
                "collector cannot be declared as \"%s\", ".
                    "expected %s ".
                "at %s:%d",
                (string) $directive,
                self::explain($policies),
                $directive->location['path'], $directive->location['position']
            ));
        }
    }
}
?>