<?php

namespace pharos\phathom
{
    use \pharos\phathom\Exception\IO as IOException;
    use \pharos\phathom\Exception\Regex as RegexException;
    use \pharos\phathom\Exception\Unexpected as UnexpectedException;

    final class Lexer
    {
        public private(set) array $config     = [];
        private string|false      $skipping   = false;
        private array             $consuming  = [];
        private array             $constants  = [];

        public function merge(File $file) : void {
            $config =
                @\parse_ini_string(
                    $file->contents, true);

            if ($config === false) {
                throw new IOException(
                    "$file does not contain valid configuration (ini syntax)");
            }

            foreach ($config as $name => &$token) {
                if (isset($this->config[$name])) {
                    throw new IOException(
                        "$file cannot redefine \"$name\", ". 
                            "already defined in {$this->config[$name]['file']}");
                }

                $token['added'] = false;
                $token['file']  =
                    (string) $file;
            }

            $this->config = \array_merge($this->config, $config);
        }

        public function known(string $token): bool {
            return isset($this->config[$token]);
        }

        public function add(File $file, array $patterns): void {
            foreach ($patterns as $pattern) {
                $this->config[$pattern] = [
                    'pattern' => $pattern,
                    'added'   => true,
                    'file'    =>
                        (string) $file,
                ];
            }
        }

        private static function delimiter(string $delimiter) : string {
            switch ($delimiter) {
                case "{": return "}";
                case "<": return ">";
                case "(": return ")";
                case "[": return "]";

                default:
                    return $delimiter;
            }
        }

        private function unwrap(string $pattern, string $file): array {
            $delim =
                $pattern[0];
            switch ($delim) {
                case "\\":
                    throw new RegexException(\sprintf(
                        "%s in %s uses an illegal delimiter, ".
                        "expected ".
                            "non-alphanumeric, ".
                            "non-whitespace, ".
                            "non-backslash, ".
                        "got backslash",
                        $pattern, $file
                    ));

                default:
                    if (\ctype_alnum($delim) ||
                        \ctype_space($delim)) {
                        throw new RegexException(\sprintf(
                            "%s in %s uses an illegal delimiter, ".
                            "expected ".
                                "non-alphanumeric, ".
                                "non-whitespace, ".
                                "non-backslash, ".
                            "got %s",
                            $pattern, $file,
                            \ctype_alnum($delim) ?
                                "alphanumeric" :
                                "whitespace"
                        ));
                    }
            }

            $end  =
                \strrpos(
                    $pattern,
                    Lexer::delimiter($delim), 1);

            if ($end === false) {
                throw new RegexException(\sprintf(
                    "%s in %s is improperly delimited, ".
                    "starting delimiter %s, ".
                    "expected ending delimiter %s",
                    $pattern, $file,
                    $delim,
                    Lexer::delimiter($delim)
                ));
            }

            return [
                \substr($pattern, 1, $end - 1),
                \substr($pattern, $end + 1),
            ];
        }

        private function wrap(array $patterns, ?string $flags = null): string|false {
            if (!$patterns) {
                return false;
            }

            return \sprintf(
                '/\G(?:%s)%s/',
                \implode('|',
                    $patterns), $flags);
        }

        public function compile(): void {
            $skipping  = [];
            $consuming = [];
            $constants     = [];
            $iterator  = 1;

            foreach ($this->config as $name => &$config) {
                [$pattern, $flags] =
                    $this->unwrap($config['pattern'], $config['file']);

                $inner = \strlen($flags)
                    ? "(?{$flags}:{$pattern})"
                    : "(?:{$pattern})";

                $config['const'] = $iterator++;

                if (isset($config['skip']) && $config['skip']) {
                    $skipping[] = $inner;
                } else {
                    $consuming[$config['const']] =
                        $this->wrap([$inner]);
                    $constants[$config['const']] = $name;
                }
            }

            $this->skipping  =
                $this->wrap($skipping, '+');
            $this->consuming = $consuming;
            $this->constants = $constants;

            $this->verify();
        }

        private function verify(): void {
            $patterns = $this->consuming;

            if ($this->skipping !== false) {
                $patterns[] = $this->skipping;
            }

            foreach ($patterns as $const => $pattern) {
                \error_clear_last();

                if (@\preg_match($pattern, '') === false) {
                    $error =
                        \error_get_last();
                    throw new RegexException(\sprintf(
                        "%s failed to compile, ".
                            "PCRE reported: %s",
                        $this->constants[$const] ?? '(skip)',
                        \preg_replace(
                            '/^[a-z_]+\(\): /i', '',
                            $error['message'])
                    ));
                }
            }
        }

        public function scan(
            File|Buffer  $input,
            int         &$position,
            array        $expected,
            string       $class = Token::class): ?Token {

            if ($position >= $input->length) {
                return null;
            }

            if ($this->skipping !== false) {
                $skipped = @\preg_match(
                    $this->skipping,
                    $input->contents, $matches,
                    0, $position);

                if ($skipped === false) {
                    // @codeCoverageIgnoreStart
                    throw new RegexException(\sprintf(
                        "skipping failed at %s:%d, ".
                            "PCRE reported: %s",
                        $input, $position,
                        \preg_last_error_msg()));
                    // @codeCoverageIgnoreEnd
                }

                if ($skipped) {
                    $position += \strlen($matches[0]);
                    if ($position >= $input->length) {
                        return null;
                    }
                }
            }

            $type   = null;
            $value  = null;
            $length = 0;
            $empty  = false;

            foreach (
                \array_intersect_key(
                    $this->consuming, $expected) as $const => $pattern) {
                $matched = @\preg_match(
                    $pattern, $input->contents, $matches, 0, $position);

                if ($matched === false) {
                    // @codeCoverageIgnoreStart
                    throw new RegexException(\sprintf(
                        "matching %s failed at %s:%d, ".
                            "PCRE reported: %s",
                        $this->constants[$const], $input, $position,
                        \preg_last_error_msg()));
                    // @codeCoverageIgnoreEnd
                }

                if (!$matched) {
                    continue;
                }

                if (($match = \strlen($matches[0])) === 0) {
                    $empty = $const;
                    continue;
                }

                if ($match > $length) {
                    $length = $match;
                    $type   = $const;
                    $value  = $matches[0];
                }
            }

            if ($type === null) {
                if ($empty !== false) {
                    throw new RegexException(\sprintf(
                        "matching %s failed at %s:%d, ".
                        "pattern matches zero characters",
                        $this->constants[$empty], $input, $position));
                }

                throw UnexpectedException::character(
                    $input->contents, [
                        'path'     => $input,
                        'position' => $position
                    ], \array_values(
                            \array_intersect_key(
                                $this->constants, $expected)));
            }

            $token = new $class(
                $type,
                [
                    'path'     => $input,
                    'position' => $position,
                ],
                $value
            );

            $position += $length;

            return $token;
        }

        public function tokenize(File|Buffer $input, string $class = Token::class): array {
            $tokens   = [];
            $position = 0;

            while ($position < $input->length) {
                $token = $this->scan(
                    $input, $position,
                    $this->consuming, $class);

                if ($token === null) {
                    break;
                }

                $tokens[] = $token;
            }

            return $tokens;
        }
    }
}
?>