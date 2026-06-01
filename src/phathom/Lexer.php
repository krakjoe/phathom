<?php

namespace pharos\phathom
{
    use \pharos\phathom\Exception\IO as IOException;
    use \pharos\phathom\Exception\Regex as RegexException;
    use \pharos\phathom\Exception\Unexpected as UnexpectedException;

    final class Lexer
    {
        public private(set) array $config = [];
        private string|false      $skipping  = false;
        private array             $consuming = [];

        public function merge(File $file) : void {
            $config =
                @\parse_ini_string(
                    $file->contents(), true);

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
            $iterator  = 1;

            foreach ($this->config as $name => &$config) {
                [$pattern, $flags] =
                    $this->unwrap($config['pattern'], $config['file']);

                $inner = \strlen($flags)
                    ? "(?{$flags}:{$pattern})"
                    : "(?:{$pattern})";

                if (isset($config['skip']) && $config['skip']) {
                    $skipping[] = $inner;
                } else {
                    $consuming[$name] =
                        $this->wrap([$inner]);
                }

                $config['const'] = $iterator++;
            }

            $this->skipping  =
                $this->wrap($skipping, '+');
            $this->consuming = $consuming;

            $this->verify();
        }

        private function verify(): void {
            $patterns = $this->consuming;

            if ($this->skipping !== false) {
                $patterns['(skip)'] = $this->skipping;
            }

            foreach ($patterns as $name => $pattern) {
                \error_clear_last();

                if (@\preg_match($pattern, '') === false) {
                    $error =
                        \error_get_last();
                    throw new RegexException(\sprintf(
                        "%s failed to compile, ".
                            "PCRE reported: %s",
                        $name,
                        \preg_replace(
                            '/^[a-z_]+\(\): /i', '',
                            $error['message'])
                    ));
                }
            }
        }

        public function tokenize(File|Buffer $input, string $class = Token::class): array {
            $tokens   = [];
            $buffered = $input->contents();
            $position = 0;
            $limit    = \strlen($buffered);

            while ($position < $limit) {
                if ($this->skipping !== false) {
                    $skipped = @\preg_match(
                        $this->skipping,
                        $buffered, $matches,
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
                        if ($position >= $limit) {
                            break;
                        }
                    }
                }

                $type   = null;
                $value  = null;
                $length = 0;
                $empty  = false;

                foreach ($this->consuming as $name => $pattern) {
                    $matched = @\preg_match(
                        $pattern, $buffered, $matches, 0, $position);

                    if ($matched === false) {
                        // @codeCoverageIgnoreStart
                        throw new RegexException(\sprintf(
                            "matching %s failed at %s:%d, ".
                                "PCRE reported: %s",
                            $name, $input, $position,
                            \preg_last_error_msg()));
                        // @codeCoverageIgnoreEnd
                    }

                    if (!$matched) {
                        continue;
                    }

                    if (($match =
                            \strlen($matches[0])) === 0) {
                        $empty = $name;
                        continue;
                    }

                    if ($match > $length) {
                        $length = $match;
                        $type   = $name;
                        $value  = $matches[0];
                    }
                }

                if ($type === null) {
                    if ($empty !== false) {
                        throw new RegexException(\sprintf(
                            "matching %s failed at %s:%d, ".
                            "pattern matches zero characters",
                            $empty, $input, $position));
                    }

                    throw UnexpectedException::character(
                        $buffered, [
                            'path'     => $input,
                            'position' => $position
                        ], \array_keys($this->consuming));
                }

                $tokens[] = new $class(
                    $this->config[$type]['const'],
                    [
                        'path'     => $input,
                        'position' => $position,
                    ],
                    $value
                );

                $position += $length;
            }

            return $tokens;
        }
    }
}
?>