<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Lexer;

    use \pharos\phathom\Exception\Undefined as UndefinedException;

    final class Compiler {
        private array  $synthetic = [];
        private array  $terminals = [];
        private array  $patterns  = [];

        public function __construct(
            private File  $file,
            private Lexer $lexer,
            private array $rules) {}

        /* Rewrite quantified symbols into synthetic nullable/recursive rules,
         * replacing each quantified reference with the synthetic rule name.
         *
         *   A*  →  __A_star__ :  ε  |  __A_star__ A     (left-recursive)
         *   A+  →  __A_plus__ :  A  |  __A_plus__ A     (left-recursive)
         *   A?  →  __A_opt__  :  ε  |  A
         */
        private function compileRules(): void {
            $synthetic = [];

            foreach ($this->rules as $rule => &$alternatives) {
                foreach ($alternatives as &$alternative) {
                    foreach ($alternative['symbols'] as &$symbol) {
                        $this->compileSymbol($rule, $symbol);

                        if ($symbol->quantifier === Quantifier::NONE) {
                            continue;
                        }

                        $base  = $symbol->name;
                        $kind  = Quantifier::name($symbol->quantifier);
                        $name =
                            \sprintf("__%s_%s__",
                                $base, $kind);

                        if (!isset($synthetic[$name])) {
                            $self = [
                                new Symbol(Token::IDENT, $name)];
                            $one  = [
                                new Symbol($symbol->type, $base)];

                            $synthetic[$name] = match ($symbol->quantifier) {
                                Quantifier::STAR => [
                                    [
                                        'symbols'  => [],
                                        'priority' => false,
                                        'action'   => null
                                    ], /* ε */
                                    [
                                        'symbols'  => \array_merge($self, $one),
                                        'priority' => $alternative['priority'],
                                        'action'   => null
                                    ], /* A* A */
                                ],
                                Quantifier::PLUS => [
                                    [
                                        'symbols'  => $one,
                                        'priority' => $alternative['priority'],
                                        'action'   => null
                                    ], /* A */
                                    [
                                        'symbols'  => \array_merge($self, $one),
                                        'priority' => $alternative['priority'],
                                        'action'   => null
                                    ], /* A+ A */
                                ],
                                Quantifier::OPTIONAL => [
                                    [
                                        'symbols'  => [],
                                        'priority' => false,
                                        'action'   => null
                                    ], /* ε */
                                    [
                                        'symbols'  => $one,
                                        'priority' => $alternative['priority'],
                                        'action'   => null
                                    ], /* A */
                                ],
                            };
                            $this->synthetic[$name] = $kind;
                        }

                        $symbol = new Symbol($symbol->type, $name, $symbol->location);
                    }
                }
            }

            $this->rules = \array_merge($this->rules, $synthetic);
        }

        private function compileSymbol(string $rule, Symbol $symbol): void {
            if (isset($this->rules[$symbol->name])) {
                return;
            }

            switch ($symbol->type) {
                case Token::PATTERN:
                    $this->patterns[$symbol->name] = true;
                break;

                default:
                    if (!$this->lexer->known($symbol->name)) {
                        throw new UndefinedException(
                            "Undefined symbol '{$symbol->name}' "
                                ."at '{$rule}' in {$this->file}");
                    }

                    $this->terminals[$symbol->name] =
                        $this->lexer->config[$symbol->name]['const'];
            }
        }

        private function compilePatterns() : void {
            if (!\count($this->patterns)) {
                return;
            }

            $patterns =
                \array_keys(
                    $this->patterns);

            $this->lexer->add($patterns);

            foreach ($patterns as $name) {
                $this->patterns[$name] =
                    $this->lexer->config[$name]['const'];
            }
        }

        public function compile() : array {
            $this->compileRules();
            $this->compilePatterns();

            return [
                $this->rules,
                $this->synthetic,
                $this->terminals,
                $this->patterns,
            ];
        }
    }
}
?>