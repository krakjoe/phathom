<?php declare(strict_types=1);

namespace pharos\phathom\Exception {
    use \pharos\phathom\File;

    class Lexer extends \pharos\phathom\Exception {
        protected static function describe(string $name, array $token) : string {
            if ( !$token['added']) {
                return \sprintf(
                    "%s with pattern \"%s\"",
                    $name, $token['pattern']);
            }
            return \sprintf("pattern \"%s\"", $token['pattern']);
        }

        public static function noconfig(File $file) : Lexer {
            return new self(\sprintf(
                "%s does not contain valid configuration (ini syntax)",
                $file));
        }

        public static function noident(File $file, string $name) : Lexer {
            return new self(\sprintf(
                "Token definition for %s ".
                    "uses an illegal name, ".
                        "(ie, it it not compatible with PHP variable names) ".
                    "names must match [a-zA-Z_][a-zA-Z0-9_]* ".
                "in %s",
                $name,
                $file));
        }

        public static function nopattern(File $file, string $name) : Lexer {
            return new self(\sprintf(
                "Token definition for %s ".
                    "is missing a pattern in %s",
                $name,
                $file));
        }

        public static function nocontent(File $file, string $name, array $token) : Lexer {
            return new self(\sprintf(
                "Token %s is erroneous, " . 
                    "matches zero characters in %s",
                self::describe($name, $token),
                $file));
        }

        public static function redefine(File $file, string $name, array $token, array $defined) : Lexer {
            return new self(\sprintf(
                "Token %s cannot be redefined in %s, " . 
                    "already defined as \"%s\" in %s",
                self::describe($name, $token),
                $file,
                $defined['pattern'],
                $defined['file'],
            ));
        }
    }
}
