<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Exception;

    final class Parser {
        const array reserve = [
            'token', 'context', 'lexer', 'include', 'start', 'optimizer', 'collector', 'engine',
        ];

        const array annotations = [
            \pharos\phathom\Grammar\Annotation\Priority::class,
            \pharos\phathom\Grammar\Annotation\Associativity::class,
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
                'collector'  => false,
                'optimizer'  => [
                    '\pharos\phathom\Grammar\Optimize\Lexer' => false,
                ],
                'engine'     => false,
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
                throw Exception\Directive::reserved(
                        $ident, self::reserve);
            }
            return (string) $ident;
        }

        private function start(Token $directive) : void {
            if ($this->directives['start'] !== false) {
                throw Exception\Directive::declared(
                    'start',
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
                throw Exception\Directive::include(
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
                throw Exception\Directive::abstract(
                    $kind,
                    $directive,
                    $this->directives[$kind]);
            }

            $abstract = (string) $directive;

            if (!\class_exists($abstract)) {
                throw Exception\Directive::autoload(
                    $kind, $directive);
            }

            $parents = \array_map(function(string $parent) : string {
                return "\\$parent";
            }, \class_parents($abstract));

            if (!\in_array($parent = match($kind) {
                'token'   => '\pharos\phathom\Token',
                'context' => '\pharos\phathom\Context'
            }, $parents)) {
                throw Exception\Directive::parent(
                    $kind, $parent, $directive);
            }

            $this->directives[$kind] = $directive;
        }

        private function engine(Token $directive) : void {
            if ($this->directives['engine'] !== false) {
                throw Exception\Directive::declared(
                    'engine',
                    $directive,
                    $this->directives['engine']);
            }

            $class = (string) $directive;

            if (!\class_exists($class)) {
                throw Exception\Directive::autoload(
                    'engine', $directive);
            }

            $interfaces = \array_map(function(string $iface) : string {
                return "\\$iface";
            }, \class_implements($class));

            if (!\in_array('\pharos\phathom\Grammar\Interface\Engine', $interfaces)) {
                throw Exception\Directive::interface(
                    'engine',
                    '\pharos\phathom\Grammar\Interface\Engine',
                    $directive);
            }

            $this->directives['engine'] = $directive;
        }

        private function optimizer(Token $directive) : void {
            $interface = (string) $directive;

            if (\array_key_exists(
                    $interface,
                    $this->directives['optimizer'])) {
                throw Exception\Directive::optimizer(
                    $directive,
                    $this->directives['optimizer'][$interface]);
            }

            if (!\class_exists($interface)) {
                throw Exception\Directive::autoload(
                    'optimizer', $directive);
            }

            $parents = \array_map(function(string $parent) : string {
                return "\\$parent";
            }, \class_parents($interface));

            if (!\in_array('\pharos\phathom\Grammar\Optimization', $parents)) {
                throw Exception\Directive::parent(
                    'optimizer',
                    '\pharos\phathom\Grammar\Optimization',
                    $directive);
            }

            $this->directives['optimizer'][$interface] = $directive;
        }

        private function collector(Token $directive) : void {
            if ($this->directives['collector'] !== false) {
                throw Exception\Directive::declared(
                    'collector',
                    $directive,
                    $this->directives['collector']);
            }

            if (Collector::from(
                    (string) $directive) == Collector::UNKNOWN) {
                throw Exception\Directive::collector(
                    $directive,
                    Collector::policies);
            }

            $this->directives['collector'] = $directive;
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
            /**
             * !The lack of defensive branches in this function is a result of the contract
             * of Lexer::tokenize!
             */
            $tokens     = $this->lexer->tokenize();
            $position   = 0;
            $count      = \count($tokens);

            $eof = function() use(&$position, $tokens) : bool {
                return ($tokens[$position]->type === Token::EOF);
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
                            $annotations = false;

                            while (($listing = $peek())) {
                                if ($listing->type === Token::LIST_END) {
                                    $consume(); /* LIST_END */

                                    while ($peek()->type === Token::ANNOTATION) {
                                        if ($annotations === false) {
                                            $annotations = [];
                                        }
                                        $annotations[] =
                                            Annotation::factory(
                                                Parser::annotations, $consume()); /* ANNOTATION */
                                    }
                                    break;
                                }

                                if ($listing->type === Token::IDENT ||
                                    $listing->type === Token::PATTERN) {
                                    $consume(); /* IDENT | PATTERN */

                                    if ($peek()->type === Token::QUANTIFIER) {
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
                                Alternative::complex($this->file, $symbols, $annotations, $action);
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
                                    $this->file,
                                    new Symbol(
                                        $token->type,
                                        $token->value,
                                        $token->location,
                                        Quantifier::from($quantify)));
                        break;

                        case Token::STRING: do {
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
                                        throw Exception\Directive::missing($directive);
                                    } else if (isset($this->directives['lexer'][$path])) {
                                        throw Exception\Directive::lexer(
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

                                case 'engine':
                                    $this->engine($directive);
                                break;

                                case 'optimizer':
                                    $this->optimizer($directive);
                                break;

                                case 'collector':
                                    $this->collector($directive);
                                break;

                                default:
                                    throw Exception\Directive::unknown(
                                        $ident, self::reserve);
                            }
                        } while ($peek()->type === Token::COMMA && $consume());
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