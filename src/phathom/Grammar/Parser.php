<?php
namespace pharos\phathom\Grammar {

    use \pharos\phathom\Grammar;

    final class Parser {
        private Grammar $grammar;
        private array $included = [];

        public function __construct(Grammar $grammar) {
            $this->grammar = $grammar;
        }

        private function directive(string $name, string $value) : void {
            switch (\strtolower($name)) {
                case "type":
                    $this->grammar->setType($value);
                break;

                case "lexer":
                    $this->grammar->setLexer($value);
                break;

                case "include":
                    $path = \sprintf(
                        "%s%s%s",
                        \dirname($this->grammar->getFile()),
                        \DIRECTORY_SEPARATOR,
                        $value
                    );

                    if (!isset($this->included[$path])) {
                        $lexer = new Lexer($path);
                        $this->parse(
                            $lexer->tokenize());
                        $this->included[$path] = $lexer;
                    }
                break;

                default:
                    throw new \Exception(
                        "Unrecognized DIRECTIVE $name, ".
                        "expected type, lexer, or include");
            }
        }

        public function parse(array $tokens): void {
            $position = 0;
            $count    = \count($tokens);

            $eof = function() use(&$position, $tokens) : bool {
                return ($tokens[$position]['type'] == 'EOF');
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
                        case 'LIST_START':
                            $start   = $consume();
                            $symbols = [];
                            while (($symbol = $peek())) {
                                if ($symbol['type'] === 'LIST_END') {
                                    $end = $consume();
                                    break;
                                }

                                if ($symbol['type'] === 'IDENT' ||
                                    $symbol['type'] === 'PATTERN') {
                                    $symbol =
                                        $consume();
                                    $quantify = $peek();
                                    if ($quantify['type'] === 'QUANTIFIER') {
                                        $symbol['quantifier'] =
                                            $consume()['value'];
                                    }
                                    $symbols[] = $symbol;
                                    continue;
                                }
                            }

                            $action = $peek();
                            if ($action['type'] === 'ACTION') {
                                $consume();
                                $this->grammar
                                    ->complexRule(
                                        $ident['value'], $symbols, $action['value']);
                            } else {
                                $this->grammar
                                    ->complexRule(
                                        $ident['value'], $symbols);
                            }
                            break;

                        case 'IDENT':
                        case 'PATTERN':
                            $token    = $consume();
                            $quantify = $peek();
                            if ($quantify['type'] === 'QUANTIFIER') {
                                $quantify =
                                    $consume()
                                        ['value'];
                            } else {
                                $quantify = null;
                            }

                            $this->grammar->expressionRule($ident['value'], $token, $quantify);
                        break;

                        case 'STRING':
                            $string =
                                $consume();
                            $this->directive(
                                $ident['value'], $string['value']);
                        break;
                    }

                    $next = $consume();

                    if ($next['type'] === 'END') {
                        break;
                    }
                }
            }
        }
    }
}
?>