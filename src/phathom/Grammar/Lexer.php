<?php
namespace pharos\phathom\Grammar {
    final class Lexer {
        private string       $path;

        private string|false $buffer;
        private int          $length   = 0;
        private int          $position = 0;

        /**
         * ── Grammar file ─────────────────────────────────────────────────
         *
         *   ident        := [^\s#:|<>(){}+*?"';]+
         *   pattern      := '<' [^>]+ '>'
         *   quantifier   := [+*?]
         *   priority     := '[' [0-9]+ ']'
         *   quantifiable := (ident | pattern) quantifier?
         *   expression   := '(' quantifiable+ ')' priority?
         *   action       := '{' code '}'
         *   end          := ';'
         *   quote        := ('\'' | '"')
         *   string       := quote [^\1]+ quote
         *   alternative  := expression action?
         *                 | quantifiable
         * 
         *   grammar      := (directive | rule)*
         *   directive    := ident COLON string end
         *   rule         := ident COLON alternative (PIPE alternative)* end
         **/

        /**
         * 'TYPE' => [
         *      'list' =>     false | [
         *          'allow'     => [],
         *      ],
         *      'allow' =>     [] 
         * ]
         * 
         * `list` may be array
         * [
         *  'allow'     => array
         * ]
         * 
         * allow arrays should include token types allowed in their respective relevant position
         **/
        private static $specification = [
            'LIST_START' => [
                'list'      => false,
                'allow'     => ['IDENT', 'PATTERN'],
            ],
            'LIST_END' => [
                'list'      => false,
                'allow'     => ['PRIORITY', 'PIPE', 'ACTION', 'END'],
            ],
            'PRIORITY' => [
                'list'      => false,
                'allow'     => ['PIPE', 'ACTION', 'END'],
            ],
            'COLON' => [
                'list'     => false,
                'allow'     => ['IDENT', 'STRING', 'LIST_START', 'PATTERN'],
            ],
            'PIPE' => [
                'list'      => false,
                'allow'     => ['IDENT', 'LIST_START', 'PATTERN'],
            ],
            'PATTERN' => [
                'list'      => [
                    'allow'     => ['IDENT', 'PATTERN', 'QUANTIFIER', 'LIST_END'],
                ],
                'allow'     => ['QUANTIFIER', 'PIPE', 'END'],
            ],
            'ACTION' => [
                'list'      => false,
                'allow'     => ['PIPE', 'END'],
            ],
            'STRING' => [
                'list'      => false,
                'allow'     => ['END'],
            ],
            'QUANTIFIER' => [
                'list'      => [
                    'allow'     => ['IDENT', 'PATTERN', 'LIST_END'],
                ],
                'allow'     => ['PIPE', 'END'],
            ],
            'IDENT' => [
                'list'     => [
                    'allow'     => ['IDENT', 'PATTERN', 'QUANTIFIER', 'LIST_END'],
                ],
                'allow'     => ['COLON', 'PIPE', 'QUANTIFIER', 'END'],
            ],
            'END' => [
                'list' => false,
                'allow' => ['IDENT', 'EOF'],
            ],
            'EOF' => [
                'list' => false,
                'allow' => [],
            ]
        ];

        public function __construct(string $path) {
            $this->path = $path;

            if (!\file_exists($this->path)) {
                throw new \Exception("$this->path does not exist");
            }

            $this->buffer = 
                @\file_get_contents(
                    $this->path);

            if ($this->buffer === false) {
                // @codeCoverageIgnoreStart
                throw new \Exception(
                    "Failed to read file: $this->path");
                // @codeCoverageIgnoreEnd
            }

            $this->length = \strlen($this->buffer);
        }

        private function comment() : array {
            $content = '';
            $start = $this->position++;

            while ($this->position < $this->length) {
                switch ($this->buffer[$this->position]) {
                    case "\n":
                        $this->position++;
                    break 2;

                    default:
                        $content .=
                            $this->buffer[
                                $this->position++];
                }
            }

            return [\trim($content), $start];
        }

        private function balance(
                    string $type,
                    string $open,
                    string $close): array {
            $depth   = 1;
            $content = '';
            $start   =
                $this->position++;

            while ($this->position < $this->length && $depth > 0) {
                if ($this->buffer[$this->position] === '\\') {
                    if (($this->position + 1) >= $this->length) {
                        throw Unexpected::escape($type, [
                            'path'     => $this->path,
                            'position' => $this->position
                        ], [
                            'open'     => $open,
                            'close'    => $close
                        ]);
                    }

                    if ($this->buffer[$this->position + 1] === $open ||
                        $this->buffer[$this->position + 1] === $close ||
                        $this->buffer[$this->position + 1] === '\\') {
                        $content .=
                            $this->buffer[
                                $this->position + 1];
                        $this->position += 2;
                    } else {
                        $content .= 
                            $this->buffer[
                                $this->position++];
                    }

                    continue;
                }

                if ($this->buffer[$this->position] === $open)  $depth++;
                if ($this->buffer[$this->position] === $close) $depth--;

                if ($depth > 0) {
                    $content .=
                        $this->buffer[
                            $this->position];
                }

                $this->position++;
            }

            if ($depth !== 0) {
                throw Unexpected::unbalanced(
                    $type,
                    $content, [
                        'path'     => $this->path,
                        'position' => $start
                    ], [
                        'open'     => $open, 
                        'close'    => $close
                    ]);
            }

            if (!\strlen($content)) {
                throw Unexpected::empty(
                    $type, [
                        'path'     => $this->path,
                        'position' => $start,
                    ], [
                        'open'     => $open,
                        'close'    => $close,
                    ]);
            }

            return [$content, $start];
        }

        private function string(string $delimiter): array {
            $content = '';
            $start   =
                $this->position++;
            $terminated = false;

            while ($this->position < $this->length) {
                if ($this->buffer[$this->position] === '\\') {
                    /* escape */
                    if ((($this->position + 1) < $this->length)) {
                        if ($this->buffer[$this->position + 1] != $delimiter) {
                            $content .=
                                $this->buffer[
                                    $this->position++];
                        } else {
                            $this->position++;
                        }
                    }

                    if ($this->position < $this->length) {
                        $content .=
                            $this->buffer[
                                $this->position++];
                    }

                    continue;
                }

                if ($this->buffer[
                        $this->position] === $delimiter) {
                    $this->position++;

                    $terminated = true;
                    break;
                }

                $content .= $this->buffer[$this->position++];
            }

            if (!$terminated) {
                throw Unexpected::unterminated(
                    'STRING',
                    $content, [
                        'path'     => $this->path,
                        'position' => $start
                    ], [
                        'open'     => $delimiter,
                        'close'    => $delimiter,
                    ]);
            }

            return [$content, $start];
        }

        private function priority() : array {
            $content = '';
            $start   =
                $this->position++;
            $terminated = false;

            while ($this->position < $this->length) {
                if ($this->buffer[
                        $this->position] === ']') {
                    $this->position++;

                    $terminated = true;
                    break;
                }

                if (!\ctype_digit($this->buffer[$this->position])) {
                    throw Unexpected::nondigit(
                        'PRIORITY',
                        $this->buffer[$this->position], [
                            'path'     => $this->path,
                            'position' => $this->position
                        ]
                    );
                }

                $content .= $this->buffer[$this->position++];
            }

            if (!$terminated) {
                throw Unexpected::unterminated(
                    'PRIORITY',
                    $content, [
                        'path'     => $this->path,
                        'position' => $start
                    ], [
                        'open'    => '[',
                        'close'   => ']',
                    ]);
            }

            if (!\strlen($content)) {
                throw Unexpected::empty(
                    'PRIORITY', [
                        'path'     => $this->path,
                        'position' => $start,
                    ], [
                        'open'     => '[',
                        'close'    => ']'
                    ]);
            }

            return [$content, $start];
        }

        public function scan() : bool {
            if ($this->position >= $this->length) {
                return false;
            }

            if (!\ctype_print($this->buffer[$this->position]) ||
                 \ctype_space($this->buffer[$this->position])) {
                return false;
            }

            switch ($this->buffer[$this->position]) {
                case '#':
                case ';':
                case ':':
                case '|':
                case '<':
                case '>':
                case '(':
                case ')':
                case '{':
                case '}':
                case '+':
                case '*':
                case '?':
                    return false;
            }

            return true;
        }
    
        private function validate(array $tokens) : array {
            $limit   = \count($tokens);

            if ($tokens[0]['type'] !== 'IDENT' &&
                $tokens[0]['type'] !== 'EOF') {
                throw Unexpected::initial($tokens[0]);
            }

            $listing    = false;

            for ($position = 0;
                 $position < $limit;
                 $position++) {
                $token =
                    $tokens[$position];
                $specification =
                    Lexer::$specification[
                        $token['type']];

                if ($token['type'] === 'LIST_END')   $listing = false;

                $rules = $listing ?
                    $specification['list'] :
                    $specification;

                if (\count($rules['allow'])) {
                    $next = $tokens[$position + 1];

                    if (!\in_array(
                            $next['type'], $rules['allow'], true)) {
                        throw Unexpected::token(
                            $token, $next, $rules['allow']);
                    }
                }

                if ($token['type'] === 'LIST_START') $listing = true;
            }

            return $tokens;
        }

        private function collect(): array {
            $tokens   = [];

            while ($this->position < $this->length) {
                if (\ctype_space(
                        $this->buffer[
                            $this->position])) {
                    $this->position++;
                    continue;
                }

                switch ($this->buffer[$this->position]) {
                    case '#':
                        [$content, $start] =
                            $this->comment();
                        /* intentionally ignored */
                    break;

                    case ';':
                        $tokens[]     = [
                            'type'     => 'END',
                            'location' => [
                                'path'     => $this->path,
                                'position' => $this->position++,
                            ],
                        ];
                    break;

                    case '(':
                        $tokens[]      = [
                            'type'     => 'LIST_START',
                            'location' => [
                                'path'     => $this->path,
                                'position' => $this->position,
                            ],
                        ];
                        $this->position++;
                    break;

                    case ')':
                        $tokens[]      = [
                            'type'     => 'LIST_END',
                            'location' => [
                                'path'     => $this->path,
                                'position' => $this->position,
                            ],
                        ];
                        $this->position++;
                    break;

                    case '[':
                        [$content, $start] =
                            $this->priority();
                        $tokens[]      = [
                            'type'     => 'PRIORITY',
                            'value'    => $content,
                            'location' => [
                                'path'     => $this->path,
                                'position' => $start,
                            ],
                        ];
                    break;

                    case ':':
                        $tokens[]      = [
                            'type'     => 'COLON',
                            'location' => [
                                'path'     => $this->path,
                                'position' => $this->position,
                            ],
                        ];
                        $this->position++;
                        break;

                    case '|':
                        $tokens[]      = [
                            'type'     => 'PIPE',
                            'location' => [
                                'path'     => $this->path,
                                'position' => $this->position,
                            ],
                        ];
                        $this->position++;
                        break;

                    case '<':
                        [$content, $start] =
                            $this->balance('PATTERN', '<', '>');
                        $tokens[]      = [
                            'type'     => 'PATTERN',
                            'value'    => $content,
                            'location' => [
                                'path'     => $this->path,
                                'position' => $start,
                            ],
                        ];
                        break;

                    case '{':
                        [$content, $start] =
                            $this->balance('ACTION', '{', '}');
                        $tokens[]      = [
                            'type'     => 'ACTION',
                            'value'    => $content,
                            'location' => [
                                'path'     => $this->path,
                                'position' => $start,
                            ],
                        ];
                        break;

                    case '\'':
                    case '"':
                        [$content, $start] =
                            $this->string(
                                $this->buffer[$this->position]);

                        $tokens[]      = [
                            'type'     => 'STRING',
                            'value'    => $content,
                            'location' => [
                                'path'     => $this->path,
                                'position' => $start,
                            ],
                        ];
                        break;

                    case '+':
                    case '*':
                    case '?':
                        $tokens[]      = [
                            'type'     => 'QUANTIFIER',
                            'value'    => 
                                $this->buffer[
                                    $this->position],
                            'location' => [
                                'path'     => $this->path,
                                'position' => $this->position++,
                            ],
                        ];
                    break;

                    default:
                        if ($this->scan()) {
                            $ident = '';
                            $start =
                                $this->position;
                            while ($this->scan()) {
                                $ident .=
                                    $this->buffer[
                                        $this->position++];
                            }

                            $tokens[]      = [
                                'type'     => 'IDENT',
                                'value'    => $ident,
                                'location' => [
                                    'path'     => $this->path,
                                    'position' => $start,
                                ],
                            ];
                        } else {
                            throw Unexpected::character(
                                $this->buffer, [
                                    'path'     => $this->path,
                                    'position' => $this->position]);
                        }
                }
            }

            $tokens[]      = [
                'type'     => 'EOF',
                'location' => [
                    'path'     => $this->path,
                    'position' => $this->position,
                ],
            ];

            return $tokens;
        }

        public function tokenize() : array {
            return $this->validate(
                $this->collect());
        }
    }
}
?>