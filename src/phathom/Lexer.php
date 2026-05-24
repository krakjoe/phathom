<?php

namespace pharos\phathom
{
    use \pharos\phathom\Exception\IO as IOException;
    use \pharos\phathom\Exception\Regex as RegexException;
    use \pharos\phathom\Exception\Unexpected as UnexpectedException;

    final class Lexer
    {
        private array|bool   $config;
        private string|false $skipping  = false;
        private array        $consuming = [];

        public function __construct(
            public private(set) File $file) {
            $this->config =
                @\parse_ini_string(
                    $this->file->contents(), true);

            if ($this->config === false) {
                throw new IOException(
                    "$this->file does not contain valid configuration (ini syntax)");
            }

            $this->compile();
        }

        public function known(string $token): bool {
            return isset($this->config[$token]);
        }

        public function add(array $patterns): void {
            foreach ($patterns as $pattern) {
                $this->config[$pattern] = [
                    'pattern' => $pattern
                ];
            }
            $this->compile();
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

        private function unwrap(string $pattern): array {
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
                        $pattern, $this->file
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
                            $pattern, $this->file,
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
                    $pattern, $this->file,
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

            foreach ($this->config as $name => $config) {
                [$pattern, $flags] =
                    $this->unwrap($config['pattern']);

                $inner = \strlen($flags)
                    ? "(?{$flags}:{$pattern})"
                    : "(?:{$pattern})";

                if (isset($config['skip']) && $config['skip']) {
                    $skipping[] = $inner;
                } else {
                    $consuming[$name] =
                        $this->wrap([$inner]);
                }
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
                        "%s in %s failed to compile, ".
                            "PCRE reported: %s",
                        $name, $this->file,
                        \preg_replace(
                            '/^[a-z_]+\(\): /i', '',
                            $error['message'])
                    ));
                }
            }
        }

        public function tokenize(File $file): array {
            $tokens   = [];
            $buffer   = $file->contents();
            $position = 0;
            $limit    = \strlen($buffer);

            while ($position < $limit) {
                if ($this->skipping !== false) {
                    $skipped = @\preg_match(
                        $this->skipping,
                        $buffer, $matches,
                        0, $position);

                    if ($skipped === false) {
                        // @codeCoverageIgnoreStart
                        throw new RegexException(\sprintf(
                            "skipping failed at %s:%d, ".
                                "PCRE reported: %s",
                            $file->path, $position,
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
                        $pattern, $buffer, $matches, 0, $position);

                    if ($matched === false) {
                        // @codeCoverageIgnoreStart
                        throw new RegexException(\sprintf(
                            "matching %s failed at %s:%d, ".
                                "PCRE reported: %s",
                            $name, $file->path, $position,
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
                            $empty, $file->path, $position));
                    }

                    throw UnexpectedException::character(
                        $buffer, [
                            'path'     => $file->path,
                            'position' => $position
                        ], \array_keys($this->consuming));
                }

                $tokens[] = [
                    'type'     => $type,
                    'value'    => $value,
                    'location' => [
                        'path'     => $file->path,
                        'position' => $position,
                    ],
                ];

                $position += $length;
            }

            return $tokens;
        }
    }
}