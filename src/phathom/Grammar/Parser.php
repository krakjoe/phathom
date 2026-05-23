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

        private function directive(array $ident, array $string) : void {
            switch (\strtolower($ident['value'])) {
                case "lexer":
                    $this->grammar->setLexer($string['value']);
                break;

                case "context":
                    $this->grammar->setContext($string['value']);
                break;

                case "include":
                    $file =
                        $this->grammar
                            ->file
                            ->relative(
                                $string['value']);

                    if (isset($this->included[$file->path])) {
                        throw UnexpectedException::include(
                            $ident,
                            $string['value'],
                            $this->included[
                                $file->path
                            ]['location']);
                    }

                    $lexer = new Lexer($file);
                    $this->parse(
                        $lexer->tokenize());
                    $this->included[$file->path] = [
                        'location' => $ident['location'],
                        'lexer'    => $lexer
                    ];
                break;

                default:
                    throw UnexpectedException::directive(
                        $ident, [
                            'lexer',
                            'context',
                            'include']);
            }
        }

        private function parse(array $tokens): void {
            $position = 0;
            $count    = \count($tokens);

            $eof = function() use(&$position, $tokens) : bool {
                return ($tokens[$position]['type'] == Token::EOF);
            };

            $peek = function () use (&$position, $tokens): ?array {
                return $tokens[$position];
            };

            $consume = function () use (&$position, $tokens): ?array {
                return $tokens[$position++];
            };

            while (!$eof()) {
                $ident =
                    $consume(); /* IDENT */
                $consume(); /* COLON */

                while (true) {
                    $next = $peek();

                    switch ($next['type']) {
                        case Token::LIST_START:
                            $start   = $consume();
                            $symbols = [];
                            $priority = false;

                            while (($symbol = $peek())) {
                                if ($symbol['type'] === Token::LIST_END) {
                                    $consume();

                                    if ($peek()['type'] === Token::PRIORITY) {
                                        $priority =
                                            (int) $consume()['value'];
                                    }
                                    break;
                                }

                                if ($symbol['type'] === Token::IDENT ||
                                    $symbol['type'] === Token::PATTERN) {
                                    $symbol =
                                        $consume();
                                    $quantify = $peek();
                                    if ($quantify['type'] === Token::QUANTIFIER) {
                                        $symbol['quantifier'] =
                                            $consume()['value'];
                                    }
                                    $symbols[] = $symbol;
                                    continue;
                                }
                            }

                            $action = $peek();
                            if ($action['type'] === Token::ACTION) {
                                $consume();
                                $this->grammar
                                    ->complexRule(
                                        $ident['value'], $symbols, $priority, $action['value']);
                            } else {
                                $this->grammar
                                    ->complexRule(
                                        $ident['value'], $symbols, $priority);
                            }
                            break;

                        case Token::IDENT:
                        case Token::PATTERN:
                            $token    = $consume();
                            $quantify = $peek();
                            if ($quantify['type'] === Token::QUANTIFIER) {
                                $quantify =
                                    $consume()
                                        ['value'];
                            } else {
                                $quantify = null;
                            }

                            $this->grammar->simpleRule($ident['value'], $token, $quantify);
                        break;

                        case Token::STRING:
                            $string =
                                $consume();
                            $this->directive($ident, $string);
                        break;
                    }

                    $next = $consume();

                    if ($next['type'] === Token::END) {
                        break;
                    }
                }
            }
        }
    }
}
?>