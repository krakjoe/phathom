<?php declare(strict_types=1);

namespace pharos\phathom
{
    use \pharos\phathom\Exception;

    final class Lexer
    {
        private const string PATTERN = '@';
    
        public private(set) array $config     = [];
        private array|false       $skipping   = false;
        private array             $consuming  = [];
        private array             $constants  = [];
        private array             $patterns   = [];

        public function merge(File $file) : void {
            $config =
                @\parse_ini_string(
                    $file->contents, true);

            if ($config === false || !\count($config)) {
                throw Exception\Lexer::noconfig($file);
            }

            foreach ($config as $name => &$token) {
                $token['added'] = false;
                $token['file']  =
                    (string) $file;
                $this->verify(
                    $file, $name, $token);
            }

            $this->config = \array_merge($this->config, $config);
        }

        public function add(File $file, array $patterns): void {
            foreach ($patterns as $pattern) {
                $token = [
                    'pattern' => $pattern,
                    'added'   => true,
                    'file'    =>
                        (string) $file,
                ];
                $this->verify(
                    $file, $pattern, $token);
                $this->config[$pattern] = $token;
            }
        }

        public function known(string $token): bool {
            return isset($this->config[$token]);
        }

        private function verify(File $file, string $name, array $config) : void {
            if (!$config['added']) {
                if (isset($this->config[$name])) {
                    throw Exception\Lexer::redefine(
                        $file, $name, $config, $this->config[$name]);
                }

                if (!\preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                    throw Exception\Lexer::noident($file, $name);
                }
            }

            if (!isset($config['pattern'])) {
                throw Exception\Lexer::nopattern($file, $name);
            }

            $delimiter =
                $config['pattern'][0];
            switch ($delimiter) {
                case "\\":
                    throw Exception\Regex::illegal(
                        $file, $name, $config, "backslash");

                default:
                    if (\ctype_alnum($delimiter) ||
                        \ctype_space($delimiter)) {
                        throw Exception\Regex::illegal(
                            $file, $name, $config,
                                \ctype_alnum($delimiter) ?
                                    "alphanumeric" :
                                    "whitespace");
                    }
            }

            $expected = Lexer::delimiter($delimiter);

            if (\strrpos(
                    $config['pattern'],
                    $expected, 1) === false) {
                throw Exception\Regex::improper(
                    $file, $name, $config, $delimiter, $expected);
            }

            \error_clear_last();

            $result =
                @\preg_match($config['pattern'], '');
            if ($result === false) {
                $error =
                    \error_get_last();
                throw Exception\Regex::compile(
                    $file, $name, $config,
                    \preg_replace(
                        '/^[a-z_]+\(\): /i', '',
                        $error['message']));
            } else if ($result >= 1) {
                throw Exception\Lexer::nocontent(
                    $file, $name, $config);
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

        private function unwrap(string $pattern): array {
            $delim =
                $pattern[0];
            $end  =
                \strrpos(
                    $pattern,
                    Lexer::delimiter($delim), 1);
            return [
                $content =
                    \substr($pattern, 1, $end - 1),
                \substr($pattern, $end + 1),
                !\preg_match(
                    '/[\\\\^$.|?*+()\[\]{}]/',
                    $content),
            ];
        }

        private function wrap(array|false $patterns, ?string $flags = null): string|false {
            if ($patterns === false) {
                return false;
            }

            return \sprintf(
                '/\G(?:%s)%s/',
                \implode('|',
                    \array_filter(
                        $patterns,
                        fn($k) => $k !== Lexer::PATTERN,
                        \ARRAY_FILTER_USE_KEY)), $flags);
        }

        public function compile(): void {
            $iterator  = 1;

            foreach ($this->config as $name => &$config) {
                [$pattern, $flags, $literal] =
                    $this->unwrap($config['pattern']);

                $inner = \strlen($flags)
                    ? "(?{$flags}:{$pattern})"
                    : "(?:{$pattern})";

                $config['const']   = $iterator++;
                $config['literal'] = $literal;

                if (isset($config['skip']) && $config['skip']) {
                    if ($this->skipping === false) {
                        $this->skipping = [];
                    }

                    $this->skipping[$config['const']] = $inner;
                } else {
                    $this->consuming[$config['const']] = $inner;
                }

                $this->constants[$config['const']] = $name;
            }

            if ($this->skipping !== false) {
                $this->skipping[Lexer::PATTERN] =
                    $this->wrap($this->skipping, '+');
            }
        }

        private function pattern(array $expected) : string {
            \ksort($expected);

            $pattern = &$this->patterns;
            foreach ($expected as $k => $_) {
                $pattern = &$pattern[$k];
            }

            if (isset($pattern[Lexer::PATTERN])) {
                return $pattern[Lexer::PATTERN];
            }

            $parts = [];
            foreach ($expected as $const => $_) {
                if (!isset($this->consuming[$const])) {
                    continue;
                }
                $parts[] = \sprintf(
                    '(?=(?P<_%d>%s))?',
                    $const,
                    $this->consuming[$const]);
            }

            return $pattern[Lexer::PATTERN] = \sprintf(
                '/\G%s/',
                \implode('', $parts));
        }

        public function expect(array $expected) : void {
            $this->pattern($expected);
        }

        public function scan(
            File|Buffer  $input,
            int         &$position,
            array        $expected,
            string       $class = Token::class,
            array        $literals = []): ?Token {

            if ($position >= $input->length) {
                return null;
            }

            if ($this->skipping !== false) {
                $skipped = @\preg_match(
                    $this->skipping[Lexer::PATTERN],
                    $input->contents, $matches,
                    0, $position);

                if ($skipped === false) {
                    // @codeCoverageIgnoreStart
                    throw Exception\Regex::skipping(
                        $input, $position,
                        \array_values(
                            \array_intersect_key(
                                $this->constants, \array_filter(
                                    $this->skipping,
                                    fn($k) => $k !== Lexer::PATTERN,
                                    \ARRAY_FILTER_USE_KEY))));
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

            $matched = @\preg_match(
                $this->pattern($expected),
                $input->contents, $matches, 0, $position);

            if ($matched === false) {
                // @codeCoverageIgnoreStart
                throw Exception\Regex::matching(
                    $input, $position,
                    \array_values(
                        \array_intersect_key(
                            $this->constants, $expected)));
                // @codeCoverageIgnoreEnd
            }

            foreach ($matches as $name => $capture) {
                if (!\is_string($name)) {
                    continue;
                }

                $match = \strlen($capture);

                if (!$match) {
                    continue;
                }

                if ($match > $length) {
                    $length = $match;
                    $type   = (int) \substr($name, 1);
                    $value  = $capture;
                }
            }

            if ($type === null) {
                throw Exception\Unexpected::character(
                    $input->contents, [
                        'path'     => $input,
                        'position' => $position
                    ], \array_values(
                            \array_intersect_key(
                                $this->constants, $expected)));
            }

            $token = $literals[$type] ?? new $class(
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
    }
}
?>