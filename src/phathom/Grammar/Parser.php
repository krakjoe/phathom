<?php
namespace pharos\phathom\Grammar {

    use \pharos\phathom\Grammar;

    final class Parser {
        private Grammar $grammar;
        private array $included = [];

        public function __construct(Grammar $grammar) {
            $this->grammar = $grammar;
        }

        private function directive(array $ident, array $string) : void {
            switch (\strtolower($ident['value'])) {
                case "type":
                    $this->grammar->setType($string['value']);
                break;

                case "lexer":
                    $this->grammar->setLexer($string['value']);
                break;

                case "include":
                    $path = \sprintf(
                        "%s%s%s",
                        \dirname($this->grammar->getFile()),
                        \DIRECTORY_SEPARATOR,
                        $string['value']
                    );

                    if (!isset($this->included[$path])) {
                        $lexer = new Lexer($path);
                        $this->parse(
                            $lexer->tokenize());
                        $this->included[$path] = $lexer;
                    }
                break;

                default:
                    throw Unexpected::directive(
                        $ident, [
                            'type',
                            'lexer',
                            'include']);
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
                            $priority = false;

                            while (($symbol = $peek())) {
                                if ($symbol['type'] === 'LIST_END') {
                                    $consume();

                                    if ($peek()['type'] === 'PRIORITY') {
                                        $priority =
                                            (int) $consume()['value'];
                                    }
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
                                        $ident['value'], $symbols, $priority, $action['value']);
                            } else {
                                $this->grammar
                                    ->complexRule(
                                        $ident['value'], $symbols, $priority);
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
                            $this->directive($ident, $string);
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