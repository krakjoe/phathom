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
                        $this->included[$path] = true;
                    }
                break;

                default:
                    throw new \Exception(
                        "Unrecognized DIRECTIVE $name, ".
                        "expected type, lexer, or include");
            }
        }

        private function process(array $tokens) : array {
            $processed = [];
            $position  = 0;
            $count     = \count($tokens);

            while ($position < $count) {
                if ($tokens[$position]['type'] == 'DIRECTIVE') {
                    $this->directive(
                        $tokens[$position]['value'],
                        $tokens[$position+2]['value']);
                    $position += 3;
                } else {
                    $processed[] =
                        $tokens[$position++];
                }
            }

            return $processed;
        }

        /*
         * ── Grammar file parsing ─────────────────────────────────────────────────
         *
         *   ident        := [^\s#:|<>(){}+*?"']+
         *   pattern      := '<' [^>]+ '>'
         *   quantifier   := [+*?]
         *   quantifiable := (ident | pattern) quantifier?
         *   expression   := '(' quantifiable+ ')'
         *   action       := '{' code '}'
         *   quote        := ('\'' | '"')
         *   string       := quote [^\1]+ quote
         *   alternative  := expression action?
         *                 | quantifiable
         * 
         *   grammar      := (directive | rule)*
         *   directive    := ident COLON string
         *   rule         := ident COLON alternative (PIPE alternative)*
         */

        public function parse(array $tokens): void {
            $tokens   = $this->process($tokens);
            $position = 0;
            $count    = \count($tokens);

            $peek = function () use (&$position, $tokens, $count): ?array {
                $index = $position;
                while ($index < $count) {
                    $peeked =
                        $tokens[$index++];
                    if ($peeked['type'] !== 'COMMENT') {
                        return $peeked;
                    }
                }
            };

            $consume = function () use (&$position, $tokens, $count): ?array {
                while ($position < $count) {
                    $consumed =
                        $tokens[$position++];
                    if ($consumed['type'] !== 'COMMENT') {
                        return $consumed;
                    }
                }
            };

            $expect = function (string  ... $types) use (&$position, &$consume): array {
                $token = $consume();
                if (!\in_array($token['type'], $types)) {
                    throw new \Exception(\sprintf(
                        "Expected %s, got %s near token %d",
                        \implode(
                            ', ', $types),
                        $token['type'],
                        $token['position']
                    ));
                }
                return $token;
            };

            while ($position < $count) {
                $name =
                    $expect('IDENT', 'EOF');

                if ($name['type'] == 'EOF') {
                    break;
                }

                $expect('COLON');

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
                                        $name['value'], $symbols, $action['value']);
                            } else {
                                $this->grammar
                                    ->complexRule(
                                        $name['value'], $symbols);
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

                            $this->grammar->expressionRule($name['value'], $token, $quantify);
                        break;
                    }

                    $next = $peek();
                    if ($next === null ||
                        $next['type'] !== 'PIPE') {
                        break;
                    }
                    $consume();
                }
            }
        }
    }
}
?>