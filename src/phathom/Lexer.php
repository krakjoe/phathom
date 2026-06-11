<?php
namespace pharos\phathom
{
    use \pharos\phathom\Exception\Lexer      as LexerException;
    use \pharos\phathom\Exception\Regex      as RegexException;
    use \pharos\phathom\Exception\Unexpected as UnexpectedException;

    final class Lexer
    {
        private const string PATTERN = '@';
    
        public private(set) array $config     = [];
        private string|false      $skipping   = false;
        private array             $consuming  = [];
        private array             $constants  = [];
        private array             $patterns   = [];

        public function merge(File $file) : void {
            $config =
                @\parse_ini_string(
                    $file->contents, true);

            if ($config === false || !\count($config)) {
                throw LexerException::noconfig($file);
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
            if (!$config['added'] && isset($this->config[$name])) {
                throw LexerException::redefine(
                    $file, $name, $config, $this->config[$name]);
            }

            if (!isset($config['pattern'])) {
                throw LexerException::nopattern($file, $name);
            }

            $delimiter =
                $config['pattern'][0];
            switch ($delimiter) {
                case "\\":
                    throw RegexException::illegal(
                        $file, $name, $config, "backslash");

                default:
                    if (\ctype_alnum($delimiter) ||
                        \ctype_space($delimiter)) {
                        throw RegexException::illegal(
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
                throw RegexException::improper(
                    $file, $name, $config, $delimiter, $expected);
            }

            \error_clear_last();

            $result =
                @\preg_match($config['pattern'], '');
            if ($result === false) {
                $error =
                    \error_get_last();
                throw RegexException::compile(
                    $file, $name, $config,
                    \preg_replace(
                        '/^[a-z_]+\(\): /i', '',
                        $error['message']));
            } else if ($result >= 1) {
                throw LexerException::nocontent(
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
            $constants = [];
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
                    $consuming[$config['const']] = $inner;
                    $constants[$config['const']] = $name;
                }
            }

            $this->skipping  =
                $this->wrap($skipping, '+');
            $this->consuming = $consuming;
            $this->constants = $constants;
        }

        private function pattern(array $expected) : string {
            \ksort($expected);

            $node = &$this->patterns;
            foreach ($expected as $k => $_) {
                $node = &$node[$k];
            }

            if (isset($node[Lexer::PATTERN])) {
                return $node[Lexer::PATTERN];
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

            return $node[Lexer::PATTERN] = \sprintf(
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
                    throw RegexException::skipping(
                        $input, $position);
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
                throw RegexException::matching(
                    $input, $position, '(alternation)', []);
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
    }
}
?>