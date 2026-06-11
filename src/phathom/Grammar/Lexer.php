<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Exception\Unexpected;

    final class Lexer {
        public private(set) File         $file;
        private             string       $buffer;
        private             int          $length;
        private             int          $position;

        /**
         * ── Grammar file ─────────────────────────────────────────────────
         *
         *   ident        := [a-zA-Z_][a-zA-Z0-9_]*
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
         * Token::TYPE => [
         *      'list' => false | [
         *          'allow' => [],
         *      ],
         *      'allow' => [] 
         * ]
         * 
         * allow arrays should contain token types allowed in the following token
         **/
        private const array specification = [
            Token::LIST_START => [
                'list' => false,
                'allow' => [
                    Token::IDENT,
                    Token::PATTERN,
                ],
            ],
            Token::LIST_END => [
                'list' => false,
                'allow' => [
                    Token::PRIORITY,
                    Token::PIPE,
                    Token::ACTION,
                    Token::END,
                ],
            ],
            Token::PRIORITY => [
                'list' => false,
                'allow' => [
                    Token::PIPE,
                    Token::ACTION,
                    Token::END,
                ],
            ],
            Token::COLON => [
                'list' => false,
                'allow' => [
                    Token::IDENT,
                    Token::STRING,
                    Token::LIST_START,
                    Token::PATTERN,
                ],
            ],
            Token::PIPE => [
                'list' => false,
                'allow' => [
                    Token::IDENT,
                    Token::LIST_START,
                    Token::PATTERN,
                ],
            ],
            Token::PATTERN => [
                'list' => [
                    'allow' => [
                        Token::IDENT,
                        Token::PATTERN,
                        Token::QUANTIFIER,
                        Token::LIST_END,
                    ],
                ],
                'allow' => [
                    Token::QUANTIFIER,
                    Token::PIPE,
                    Token::END,
                ],
            ],
            Token::ACTION => [
                'list' => false,
                'allow' => [
                    Token::PIPE,
                    Token::END,
                ],
            ],
            Token::STRING => [
                'list' => false,
                'allow' => [
                    Token::END,
                ],
            ],
            Token::QUANTIFIER => [
                'list' => [
                    'allow' => [
                        Token::IDENT,
                        Token::PATTERN,
                        Token::LIST_END,
                    ],
                ],
                'allow' => [
                    Token::PIPE,
                    Token::END,
                ],
            ],
            Token::IDENT => [
                'list' => [
                    'allow' => [
                        Token::IDENT,
                        Token::PATTERN,
                        Token::QUANTIFIER,
                        Token::LIST_END,
                    ],
                ],
                'allow' => [
                    Token::COLON,
                    Token::PIPE,
                    Token::QUANTIFIER,
                    Token::END,
                ],
            ],
            Token::END => [
                'list' => false,
                'allow' => [
                    Token::IDENT,
                    Token::EOF,
                ],
            ],
            Token::EOF => [
                'list' => false,
                'allow' => [],
            ]
        ];

        public function __construct(File $file) {
            $this->file     = $file;
            $this->buffer   =
                $this->file->contents;
            $this->length   =
                $this->file->length;
            $this->position = 0;
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
                    int    $type,
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
                            'path'     => $this->file->path,
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
                        'path'     => $this->file->path,
                        'position' => $start
                    ], [
                        'open'     => $open, 
                        'close'    => $close
                    ]);
            }

            if (!\strlen($content)) {
                throw Unexpected::empty(
                    $type, [
                        'path'     => $this->file->path,
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
                    Token::STRING,
                    $content, [
                        'path'     => $this->file->path,
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
                        Token::PRIORITY,
                        $this->buffer[$this->position], [
                            'path'     => $this->file->path,
                            'position' => $this->position
                        ]
                    );
                }

                $content .= $this->buffer[$this->position++];
            }

            if (!$terminated) {
                throw Unexpected::unterminated(
                    Token::PRIORITY,
                    $content, [
                        'path'     => $this->file->path,
                        'position' => $start
                    ], [
                        'open'    => '[',
                        'close'   => ']',
                    ]);
            }

            if (!\strlen($content)) {
                throw Unexpected::empty(
                    Token::PRIORITY, [
                        'path'     => $this->file->path,
                        'position' => $start,
                    ], [
                        'open'     => '[',
                        'close'    => ']'
                    ]);
            }

            return [$content, $start];
        }

        private function character(bool $initial) : bool {
            if ($this->position >= $this->length) {
                return false;
            }

            $character =
                $this->buffer[
                    $this->position];

            if ($initial === true) {
                if (\ctype_digit($character)) {
                    return false;
                }
            }

            return \ctype_alnum($character) || $character === '_';
        }

        private function ident() : array {
            if ($this->character(true)) {
                $start =
                    $this->position;
                $content =
                    $this->buffer[
                        $this->position++];
                while ($this->character(false)) {
                    $content .=
                        $this->buffer[
                            $this->position++];
                }

                return [$content, $start];
            } else {
                throw Unexpected::character(
                    $this->buffer, [
                        'path'     => $this->file->path,
                        'position' => $this->position
                    ], ['IDENT']);
            }
        }
    
        private function validate(array $tokens) : array {
            $limit   = \count($tokens);

            if ($tokens[0]->type !== Token::IDENT &&
                $tokens[0]->type !== Token::EOF) {
                throw Unexpected::initial($tokens[0]);
            }

            $listing = false;

            for ($position = 0;
                 $position < $limit;
                 $position++) {
                $token =
                    $tokens[$position];
                $specification =
                    self::specification[
                        $token->type];

                if ($token->type === Token::LIST_END)   $listing = false;

                $rules = $listing ?
                    $specification['list'] :
                    $specification;

                if (\count($rules['allow'])) {
                    $next = $tokens[$position + 1];

                    if (!\in_array(
                            $next->type, $rules['allow'], true)) {
                        throw Unexpected::token(
                            $token, $next, \array_map(function($rule) {
                                return Token::string($rule);
                            }, $rules['allow']));
                    }
                }

                if ($token->type === Token::LIST_START) $listing = true;
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
                        $tokens[] = new Token(
                            Token::END, [
                                'path'     => $this->file->path,
                                'position' => $this->position++,
                            ]);
                    break;

                    case '(':
                        $tokens[] = new Token(
                            Token::LIST_START, [
                                'path'     => $this->file->path,
                                'position' => $this->position++,
                            ]);
                    break;

                    case ')':
                        $tokens[] = new Token(
                            Token::LIST_END, [
                                'path'     => $this->file->path,
                                'position' => $this->position++,
                            ]);
                    break;

                    case '[':
                        [$content, $start] =
                            $this->priority();
                        $tokens[] = new Token(
                            Token::PRIORITY, [
                                'path'     => $this->file->path,
                                'position' => $start,
                            ], $content);
                    break;

                    case ':':
                        $tokens[] = new Token(
                            Token::COLON, [
                                'path'     => $this->file->path,
                                'position' => $this->position++,
                            ]);
                        break;

                    case '|':
                        $tokens[] = new Token(
                            Token::PIPE, [
                                'path'     => $this->file->path,
                                'position' => $this->position++,
                            ]);
                        break;

                    case '<':
                        [$content, $start] =
                            $this->balance(Token::PATTERN, '<', '>');
                        $tokens[] = new Token(
                            Token::PATTERN, [
                                'path'     => $this->file->path,
                                'position' => $start,
                            ], $content);
                        break;

                    case '{':
                        [$content, $start] =
                            $this->balance(Token::ACTION, '{', '}');
                        $tokens[] = new Token(
                            Token::ACTION, [
                                'path'     => $this->file->path,
                                'position' => $start,
                            ], $content);
                        break;

                    case '\'':
                    case '"':
                        [$content, $start] =
                            $this->string(
                                $this->buffer[$this->position]);

                        $tokens[] = new Token(
                            Token::STRING, [
                                'path'     => $this->file->path,
                                'position' => $start,
                            ], $content);
                        break;

                    case '+':
                    case '*':
                    case '?':
                        $tokens[] = new Token(
                            Token::QUANTIFIER, [
                                'path'     => $this->file->path,
                                'position' => $this->position,
                            ], $this->buffer[$this->position++]);
                    break;

                    default:
                        [$content, $start] =
                            $this->ident();

                        $tokens[] = new Token(
                            Token::IDENT, [
                                'path'     => $this->file->path,
                                'position' => $start,
                            ], $content);
                }
            }

            $tokens[] = new Token(
                Token::EOF, [
                    'path'     => $this->file->path,
                    'position' => $this->position,
                ]);

            return $tokens;
        }

        /**
         * The contract of this function must always be to return a validated
         * token stream.
         * 
         * The consumer should not have to use the result defensively.
         * 
         * In pratice this means maintaining the specification table properly
         *      (according to grammar specification).
         */
        public function tokenize() : array {
            return $this->validate(
                $this->collect());
        }
    }
}
?>