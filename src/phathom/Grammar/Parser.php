<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;

    use \pharos\phathom\Exception\Directive  as DirectiveException;

    final class Parser {
        private Lexer $lexer;

        public function __construct(
            public private(set) File    $file,
            private             array   $included = [],
            private             array   $directives = [
                'lexer'   => [],
                'context' => false,
                'token'   => false,
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

        private function include(Token $directive, Token $include) : array {
            $file =
                $this->file
                    ->relative((string) $include);

            if (isset($this->included[$file->path])) {
                throw DirectiveException::include(
                    $directive,
                    (string) $include,
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

        private function abstract(Token $ident, Token $string) : void {
            $directive = (string) $ident;

            if ($this->directives[$directive] !== false) {
                throw DirectiveException::abstract(
                    $ident,
                    $this->directives);
            }

            $abstract  = (string) $string;

            if (!\class_exists($abstract)) {
                throw new DirectiveException(
                    "{$abstract} does not exist, it must be autoloadable");
            }

            $parents = \array_map(function(string $parent) : string {
                return "\\$parent";
            }, \class_parents($abstract));

            if (!\in_array($parent = match($directive) {
                'token'   => '\pharos\phathom\Token',
                'context' => '\pharos\phathom\Context'
            }, $parents)) {
                throw new DirectiveException(
                    "{$abstract} does not extend {$parent}");
            }

            $this->directives[$directive] = $string;
        }

        /**
         * The contract of this function must always be to return valid AST
         * 
         * IE, the consumer should not have to use the structures defensively
         *      (they are all over hot paths).
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

                            $this->rules[(string) $ident][] =
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

                            $this->rules[(string) $ident][] =
                                Alternative::simple(
                                    new Symbol(
                                        $token->type,
                                        $token->value,
                                        $token->location,
                                        Quantifier::from($quantify)));
                        break;

                        case Token::STRING:
                            $string =
                                $consume(); /* STRING */
                            switch ((string) $ident) {                                    
                                case 'token':
                                case 'context':
                                    $this->abstract($ident, $string);
                                break;

                                case 'lexer':
                                    $path = $this->file
                                        ->realpath((string) $string);
                                    if ($path === false) {
                                        throw DirectiveException::missing($string, $ident);
                                    } else if (isset($this->directives['lexer'][$path])) {
                                        throw DirectiveException::lexer(
                                            $string,
                                            $this->directives['lexer'][$path]);
                                    }
                                    $this->directives['lexer'][$path] = $string;
                                break;

                                case 'include':
                                    [
                                        $this->included,
                                        $this->directives,
                                        $this->rules
                                    ] = $this->include($ident, $string);
                                break;

                                default:
                                    throw DirectiveException::unknown(
                                        $ident, [
                                            'lexer',
                                            'token',
                                            'context',
                                            'include']);
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