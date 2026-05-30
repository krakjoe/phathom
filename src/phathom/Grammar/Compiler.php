<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Lexer;

    use \pharos\phathom\Exception\Undefined as UndefinedException;
    use \pharos\phathom\Exception\Priority as PriorityException;

    final class Compiler {
        private array  $synthetic = [];
        private array  $terminals = [];
        private array  $patterns  = [];

        public function __construct(
            private File  $file,
            private Lexer $lexer,
            private array $rules) {}

        private function compileRules(): void {
            $synthetic = [];

            foreach ($this->rules as $rule => &$alternatives) {
                $prioritized =
                    $alternatives[0]->priority !== false;

                foreach ($alternatives as $aid => &$alternative) {
                    if (($prioritized === true  && $alternative->priority === false) ||
                        ($prioritized === false && $alternative->priority !== false)) {
                        throw new PriorityException(\sprintf(
                            "Priority annotation inconsistent for ".
                            "alternative %d at '%s' in %s",
                            $aid + 1,
                            $rule,
                            $this->file
                        ));
                    }

                    foreach ($alternative->symbols as &$symbol) {
                        $this->compileSymbol($rule, $symbol);

                        if ($symbol->quantifier === Quantifier::NONE) {
                            continue;
                        }

                        $this->compileSynthetic(
                            $rule, $symbol, $synthetic);
                    }
                }

                if ($prioritized && \count($alternatives) === 1) {
                    throw new PriorityException(\sprintf(
                        "Priority annotation inert for ".
                        "single alternative at '%s' in %s",
                        $rule,
                        $this->file));
                }
            }

            $this->rules = \array_merge($this->rules, $synthetic);
        }

        private function compileSymbol(string $rule, Symbol &$symbol): void {
            if (isset($this->rules[$symbol->name])) {
                return;
            }

            switch ($symbol->type) {
                case Token::PATTERN:
                    $this->patterns[$symbol->name] = true;
                break;

                default:
                    if (!$this->lexer->known($symbol->name)) {
                        throw new UndefinedException(\sprintf(
                            "Undefined symbol '%s' at '%s' in %s",
                            $symbol->name,
                            $rule,
                            $this->file
                        ));
                    }

                    $this->terminals[$symbol->name] =
                        $this->lexer->config[$symbol->name]['const'];
            }
        }

        /* Rewrite quantified symbols into synthetic nullable/recursive rules,
         * replacing each quantified reference with the synthetic rule name.
         *
         *   A*  →  __A_star__ :  ε  |  __A_star__ A     (left-recursive)
         *   A+  →  __A_plus__ :  A  |  __A_plus__ A     (left-recursive)
         *   A?  →  __A_opt__  :  ε  |  A
         */
        private function compileSynthetic(string $rule, Symbol &$symbol, array &$synthetic) {
            $base  = $symbol->name;
            $kind  = Quantifier::name($symbol->quantifier);
            $name =
                \sprintf("__%s_%s__",
                    $base, $kind);

            if (!isset($this->synthetic[$name])) {
                $self = [
                    new Symbol(Token::IDENT, $name)];
                $one  = [
                    new Symbol($symbol->type, $base)];

                $synthetic[$name] = match ($symbol->quantifier) {
                    Quantifier::STAR => [
                        new Alternative([]),                        /* ε */
                        new Alternative(\array_merge($self, $one)), /* A* A */
                    ],
                    Quantifier::PLUS => [
                        new Alternative($one),                      /* A */
                        new Alternative(\array_merge($self, $one)), /* A+ A */
                    ],
                    Quantifier::OPTIONAL => [
                        new Alternative([]),                        /* ε */
                        new Alternative($one),                      /* A */
                    ],
                };
                $this->synthetic[$name] = $kind;
            }

            $symbol = new Symbol($symbol->type, $name, $symbol->location);
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