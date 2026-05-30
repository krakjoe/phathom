<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\Grammar;

    use \pharos\phathom\Exception\Unexpected as UnexpectedException;

    final class Parser {
        public private(set) Grammar $grammar;
        private             array   $included = [];

        public function __construct(
            Grammar $grammar
        ) {
            $this->grammar = $grammar;

            $file =
                $this->grammar->file;
            $lexer = new Lexer($file);

            $this->included[$file->path] = [
                'location' => [
                    'path' => $file->path,
                    'position' => 0,
                ],
                'lexer' => $lexer
            ];

            $this->parse(
                $lexer->tokenize());
        }

        private function directive(Token $ident, Token $string) : void {
            switch (\strtolower($ident->value)) {
                case "lexer":
                    $this->grammar->setLexer($string->value);
                break;

                case "token":
                    $this->grammar->setAbstract(
                        'token',
                        $string->value,
                        '\pharos\phathom\Token');
                break;

                case "context":
                    $this->grammar->setAbstract(
                        'context',
                        $string->value,
                        '\pharos\phathom\Context');
                break;

                case "include":
                    $file =
                        $this->grammar
                            ->file
                            ->relative(
                                $string->value);

                    if (isset($this->included[$file->path])) {
                        throw UnexpectedException::include(
                            $ident,
                            $string->value,
                            $this->included[
                                $file->path
                            ]['location']);
                    }

                    $lexer = new Lexer($file);

                    $this->included[$file->path] = [
                        'location' => $ident->location,
                        'lexer'    => $lexer
                    ];

                    $this->parse(
                        $lexer->tokenize());
                break;

                default:
                    throw UnexpectedException::directive(
                        $ident, [
                            'lexer',
                            'token',
                            'context',
                            'include']);
            }
        }

        private function parse(array $tokens): void {
            $position = 0;
            $count    = \count($tokens);

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
                                            (int) $consume() /* PRIORITY */
                                                ->value;
                                    }
                                    break;
                                }

                                if ($listing->type === Token::IDENT ||
                                    $listing->type === Token::PATTERN) {
                                    $consume(); /* IDENT | PATTERN */

                                    if ($peek()->type == Token::QUANTIFIER) {
                                        $quantify =
                                            $consume()
                                                ->value; /* QUANTIFIER */
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
                                    $consume(); /* ACTION */
                                $this->grammar
                                    ->complexAlternative(
                                        $ident->value, $symbols, $priority, $action->value);
                            } else {
                                $this->grammar
                                    ->complexAlternative(
                                        $ident->value, $symbols, $priority);
                            }
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

                            $this->grammar->simpleAlternative(
                                $ident->value,
                                new Symbol(
                                    $token->type,
                                    $token->value,
                                    $token->location,
                                    Quantifier::from($quantify)));
                        break;

                        case Token::STRING:
                            $string =
                                $consume(); /* STRING */
                            $this->directive($ident, $string);
                        break;
                    }

                    if ($consume() /* END | PIPE */
                            ->type === Token::END) {
                        break;
                    }
                }
            }
        }
    }
}
?>