<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;

    use \pharos\phathom\Exception\Directive;

    final class Parser {
        const array reserve = [
            'token', 'context', 'lexer', 'include', 'start', 'optimizer'
        ];

        private Lexer $lexer;
        public function __construct(
            public private(set) File    $file,
            private             array   $included = [],
            private             array   $directives = [
                'lexer'      => [],
                'context'    => false,
                'token'      => false,
                'start'      => false,
                'optimizer'  => [
                    '\pharos\phathom\Earley\Optimize\Lexer' => false,
                ],
            ],
            private             array   $rules = [],
        ) {
            $this->lexer = new Lexer($this->file);

            if (!isset($this->included[$this->file->path])) {
                $this->included[$this->file->path] = [
                    'path'     => $this->file->path,
                    'position' => 0,
                ];
            }
        }

        private static function reserved(Token $ident) : string {
            if (\in_array((string) $ident, self::reserve)) {
                throw Directive::reserved(
                        $ident, self::reserve);
            }
            return (string) $ident;
        }

        private function start(Token $directive) : void {
            if ($this->directives['start'] !== false) {
                throw Directive::start(
                    $directive,
                    $this->directives['start']);
            }
            $this->directives['start'] = $directive;
        }

        private function include(Token $directive) : array {
            $file =
                $this->file
                    ->relative((string) $directive);

            if (isset($this->included[$file->path])) {
                throw Directive::include(
                    $directive,
                    $this->included[
                        $file->path
                    ]);
            }

            $this->included[$file->path] =
                $directive->location;

            $parser = new self(
                $file,
                $this->included,
                $this->directives,
                $this->rules);

            return $parser->parse();
        }

        private function abstract(string $kind, Token $directive) : void {            
            if ($this->directives[$kind] !== false) {
                throw Directive::abstract(
                    $kind,
                    $directive,
                    $this->directives[$kind]);
            }

            $abstract = (string) $directive;

            if (!\class_exists($abstract)) {
                throw Directive::autoload(
                    $kind, $directive);
            }

            $parents = \array_map(function(string $parent) : string {
                return "\\$parent";
            }, \class_parents($abstract));

            if (!\in_array($parent = match($kind) {
                'token'   => '\pharos\phathom\Token',
                'context' => '\pharos\phathom\Context'
            }, $parents)) {
                throw Directive::parent(
                    $kind, $parent, $directive);
            }

            $this->directives[$kind] = $directive;
        }

        public function optimizer(Token $directive) : void {
            $interface = (string) $directive;

            if (\array_key_exists(
                    $interface,
                    $this->directives['optimizer'])) {
                throw Directive::optimizer(
                    $directive,
                    $this->directives['optimizer'][$interface]);
            }

            if (!\class_exists($interface)) {
                throw Directive::autoload(
                    'optimizer', $directive);
            }

            $parents = \array_map(function(string $parent) : string {
                return "\\$parent";
            }, \class_parents($interface));

            if (!\in_array('\pharos\phathom\Grammar\Optimization', $parents)) {
                throw Directive::parent(
                    'optimizer',
                    '\pharos\phathom\Grammar\Optimization',
                    $directive);
            }

            $this->directives['optimizer'][$interface] = $directive;
        }

        /**
         * The contract of this function must always be to return valid structures.
         * 
         * IE, the consumer should not have to use the structures defensively.
         * 
         * In practice this means, check files exists and abstracts are the correct
         * shape.
         */
        public function parse(): array {
            $tokens     = $this->lexer->tokenize();
            $position   = 0;
            $count      = \count($tokens);

            $eof = function() use(&$position, $tokens) : bool {
                return ($tokens[$position]->type == Token::EOF);
            };

            $peek = function () use (&$position, $tokens): ?Token {
                return $tokens[$position];
            };

            $consume = function () use (&$position, $tokens): ?Token {
                return $tokens[$position++];
            };

            while (!$eof()) {
                $ident =
                    $consume(); /* IDENT */
                $consume(); /* COLON */

                while (true) {
                    switch ($peek()->type) {
                        case Token::LIST_START:
                            $consume(); /* LIST_START */
                            $symbols = [];
                            $priority = false;

                            while (($listing = $peek())) {
                                if ($listing->type === Token::LIST_END) {
                                    $consume(); /* LIST_END */

                                    if ($peek()->type === Token::PRIORITY) {
                                        $priority =
                                            (int) (string) $consume(); /* PRIORITY */
                                    }
                                    break;
                                }

                                if ($listing->type === Token::IDENT ||
                                    $listing->type === Token::PATTERN) {
                                    $consume(); /* IDENT | PATTERN */

                                    if ($peek()->type == Token::QUANTIFIER) {
                                        $quantify =
                                            (string) $consume(); /* QUANTIFIER */
                                    } else {
                                        $quantify = null;
                                    }

                                    $symbols[] = new Symbol(
                                        $listing->type,
                                        $listing->value,
                                        $listing->location,
                                        Quantifier::from($quantify));
                                    continue;
                                }
                            }

                            if ($peek()->type === Token::ACTION) {
                                $action = 
                                    (string) $consume(); /* ACTION */
                            } else {
                                $action = null;
                            }

                            $this->rules[self::reserved($ident)][] =
                                Alternative::complex($symbols, $priority, $action);
                            break;

                        case Token::IDENT:
                        case Token::PATTERN:
                            $token = $consume(); /* IDENT | PATTERN */
                            if ($peek()->type === Token::QUANTIFIER) {
                                $quantify =
                                    $consume() /* QUANTIFIER */
                                        ->value;
                            } else {
                                $quantify = null;
                            }

                            $this->rules[self::reserved($ident)][] =
                                Alternative::simple(
                                    new Symbol(
                                        $token->type,
                                        $token->value,
                                        $token->location,
                                        Quantifier::from($quantify)));
                        break;

                        case Token::STRING:
                            $directive =
                                $consume(); /* STRING */
                            switch ((string) $ident) {                                    
                                case 'token':
                                    $this->abstract('token', $directive);
                                break;

                                case 'context':
                                    $this->abstract('context', $directive);
                                break;

                                case 'lexer':
                                    $path =
                                        $this->file
                                            ->realpath(
                                                (string) $directive);
                                    if ($path === false) {
                                        throw Directive::missing($directive);
                                    } else if (isset($this->directives['lexer'][$path])) {
                                        throw Directive::lexer(
                                            $directive,
                                            $this->directives['lexer'][$path]);
                                    }
                                    $this->directives['lexer'][$path] = $directive;
                                break;

                                case 'include':
                                    [
                                        $this->included,
                                        $this->directives,
                                        $this->rules
                                    ] = $this->include($directive);
                                break;

                                case 'start':
                                    $this->start($directive);
                                break;

                                case 'optimizer':
                                    $this->optimizer($directive);
                                break;

                                default:
                                    throw Directive::unknown(
                                        $ident, self::reserve);
                            }
                        break;
                    }

                    if ($consume() /* END | PIPE */
                            ->type === Token::END) {
                        break;
                    }
                }
            }

            return [$this->included, $this->directives, $this->rules];
        }
    }
}
?>