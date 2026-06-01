<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Lexer;

    use \pharos\phathom\Exception\Undefined;
    use \pharos\phathom\Exception\Priority;

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
                $priorities  = [];
                $prioritized =
                    $alternatives[0]
                        ->priority !== false;

                foreach ($alternatives as $aid => &$alternative) {
                    if (($prioritized === true  && $alternative->priority === false) ||
                        ($prioritized === false && $alternative->priority !== false)) {
                        throw Priority::inconsistent(
                            $this->file, $rule, $aid + 1);
                    }

                    if ($prioritized === true) {
                        if (\in_array($alternative->priority, $priorities)) {
                            throw Priority::ambiguous(
                                $this->file, $rule, $aid + 1);
                        }
                        $priorities[] =
                            $alternative->priority;
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
                    throw Priority::inert($this->file, $rule);
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
                        throw Undefined::symbol(
                            $this->file, $rule, $symbol->name);
                    }

                    $this->terminals[$symbol->name] = true;
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
            $name =
                \sprintf("__%s_%s__",
                    $base,
                    Quantifier::name($symbol->quantifier));

            if (!isset($this->synthetic[$name])) {
                $self = [
                    new Symbol(Token::IDENT, $name)];
                $one  = [
                    new Symbol($symbol->type, $base)];

                $synthetic[$name] = match ($symbol->quantifier) {
                    Quantifier::STAR => [
                        Alternative::synthetic([],                        Quantifier::STAR), /* ε */
                        Alternative::synthetic(\array_merge($self, $one), Quantifier::STAR), /* A* A */
                    ],
                    Quantifier::PLUS => [
                        Alternative::synthetic($one,                      Quantifier::PLUS), /* A */
                        Alternative::synthetic(\array_merge($self, $one), Quantifier::PLUS), /* A+ A */
                    ],
                    Quantifier::OPTIONAL => [
                        Alternative::synthetic([],                        Quantifier::OPTIONAL), /* ε */
                        Alternative::synthetic($one,                      Quantifier::OPTIONAL), /* A */
                    ],
                };
            }

            $symbol = new Symbol($symbol->type, $name, $symbol->location);
        }

        private function compileTokens() : void {
            if (\count($this->patterns)) {
                $this->lexer->add(
                    $this->file, \array_keys(
                        $this->patterns));
            }
            $this->lexer->compile();
        }

        private function compileConstants() : void {
            foreach (\array_keys($this->terminals) as $terminal) {
                $this->terminals[$terminal] =
                    $this->lexer->config[$terminal]['const'];
            }

            foreach (\array_keys($this->patterns) as $pattern) {
                $this->patterns[$pattern] =
                    $this->lexer->config[$pattern]['const'];
            }
        }

        public function compile() : array {
            $this->compileRules();
            $this->compileTokens();
            $this->compileConstants();

            return [
                $this->rules,
                $this->terminals,
                $this->patterns,
            ];
        }
    }
}
?>