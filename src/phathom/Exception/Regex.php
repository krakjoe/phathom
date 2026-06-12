<?php declare(strict_types=1);

namespace pharos\phathom\Exception {
    use \pharos\phathom\File;
    use \pharos\phathom\Buffer;

    final class Regex extends \pharos\phathom\Exception\Lexer {

        public static function illegal(File $file, string $name, array $token, string $reason) : Regex {
            return new self(\sprintf(
                "Token %s uses an illegal delimiter, ".
                "expected ".
                    "non-alphanumeric, ".
                    "non-whitespace, ".
                    "non-backslash, ".
                "got %s in %s",
                self::describe($name, $token),
                $reason, $file
            ));
        }

        public static function improper(File $file, string $name, array $token, string $started, string $expected) : Regex {
            return new self(\sprintf(
                "Token %s is improperly delimited, ".
                    "starting delimiter %s ".
                    "expected ending delimiter %s ".
                "in %s",
                self::describe($name, $token),
                $started,
                $expected,
                $file));
        }

        public static function compile(File $file, string $name, array $token, string $message) : Lexer {
            return new self(\sprintf(
                "Token %s failed to compile. ". 
                    "PCRE reported: %s in %s",
                self::describe($name, $token),
                $message, $file
            ));
        }

        // @codeCoverageIgnoreStart
        public static function skipping(File|Buffer $input, int $position) : Regex {
            return new self(\sprintf(
                "The PCRE engine encountered an error ".
                    "while skipping ".
                "PCRE reported: %s at %s:%d",
                \preg_last_error_msg(), $input, $position));
        }

        public static function matching(File|Buffer $input, int $position, string $name, array $token) : Regex {
            return new self(\sprintf(
                "The PCRE engine encountered an error ".
                    "while matching ".
                "Token %s defined in %s, ".
                "PCRE reported: %s at %s:%d",
                self::describe($name, $token),
                $token['file'],
                \preg_last_error_msg(), $input, $position));
        }
        // @codeCoverageIgnoreEnd
    }
}
?>