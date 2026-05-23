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

                        if ($symbol['quantifier'] === null) {
                            continue;
                        }

                        $base  = $symbol['name'];
                        $type  = $symbol['quantifier'];
                        $kind  = match ($type) {
                            '*'     => 'star',
                            '+'     => 'plus',
                            '?'     => 'opt',
                        };
                        $name =
                            \sprintf("__%s_%s__",
                                $base, $kind);

                        if (!isset($synthetic[$name])) {
                            $self = [[
                                'name'       => $name,
                                'type'       => Token::IDENT,
                                'quantifier' => null
                            ]];

                            $one = [[
                                'name'       => $base,
                                'type'       => $symbol['type'],
                                'quantifier' => null
                            ]];

                            $synthetic[$name] = match ($kind) {
                                'star' => [
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
                                'plus' => [
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
                                'opt' => [
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

                        $symbol['name']       = $name;
                        $symbol['quantifier'] = null;
                    }
                }
            }

            $this->rules = \array_merge($this->rules, $synthetic);
        }

        private function compileSymbol(string $rule, array $symbol): void {
            if (isset($this->rules[$symbol['name']])) {
                return;
            }

            switch ($symbol['type']) {
                case Token::PATTERN:
                    $this->patterns[$symbol['name']] = true;
                break;

                default:
                    if (!$this->lexer->known($symbol['name'])) {
                        throw new UndefinedException(
                            "Undefined symbol '{$symbol['name']}' "
                                ."at '{$rule}' in {$this->file}");
                    }

                    $this->terminals[$symbol['name']] = true;
            }
        }

        public function compile() : array {
            $this->compileRules();

            if (\count($this->patterns)) {
                $this->lexer->add(
                    \array_keys(
                        $this->patterns));
            }

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