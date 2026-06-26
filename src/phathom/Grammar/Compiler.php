<?php declare(strict_types=1);
/*
  +----------------------------------------------------------------------+
  | phathom                                                              |
  +----------------------------------------------------------------------+
  | Copyright (c) Joe Watkins 2026                                       |
  +----------------------------------------------------------------------+
  | This source file is subject to the BSD 3-Clause License bundled     |
  | with this package in the file LICENSE.                               |
  +----------------------------------------------------------------------+
  | Author: krakjoe                                                      |
  +----------------------------------------------------------------------+
 */

namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Lexer;
    use \pharos\phathom\Exception;

    use \pharos\phathom\Grammar\Associativity;

    final class Compiler {
        private string     $start     = 'unit';
        private array      $terminals = [];
        private array      $patterns  = [];
        private array      $symbols   = [];
        private array      $abstracts = [
            'token'   => '\pharos\phathom\Token',
            'context' => '\pharos\phathom\Context'
        ];
        private Collector   $collector = Collector::DEFAULT;
        private string      $engine =    \pharos\phathom\GLR\Engine::class;
        private array       $optimizations = [];

        public function __construct(
            private File   $file,
            private Lexer  $lexer,
            private array  $directives,
            private array  $rules) {}

        private function compileDirectives() : void {
            foreach ($this->directives['lexer'] as $path => $location) {
                $this->lexer
                    ->merge(new File($path));
            }

            if ($this->directives['context'] !== false) {
                $this->abstracts['context'] = (string)
                    $this->directives['context'];
            }

            if ($this->directives['token'] !== false) {
                $this->abstracts['token'] = (string)
                    $this->directives['token'];
            }

            if ($this->directives['start'] !== false) {
                $this->start = (string)
                    $this->directives['start'];
            }

            if ($this->directives['collector'] !== false) {
                $this->collector =
                    Collector::from(
                        (string)
                            $this->directives['collector']);
            }

            if ($this->directives['optimizer'] !== false) {
                $this->optimizations =
                    $this->directives['optimizer'];
            }

            if (($override = \getenv("PHATHOM_ENGINE"))) {
                $this->engine =
                    (string) $override;
            } else if ($this->directives['engine'] !== false) {
                $this->engine =
                    (string)
                        $this->directives['engine'];
            }

            if (empty($this->rules)) {
                throw Exception\Undefined::rules($this->file);
            }

            if (!isset($this->rules[$this->start])) {
                throw Exception\Undefined::start(
                    $this->file, $this->start);
            }
        }

        private function compileRules(): void {
            $synthetic = [];

            foreach ($this->rules as $rule => &$alternatives) {
                $this->compileAlternatives(
                    $rule, $alternatives, $synthetic);
            }

            $this->rules = \array_merge($this->rules, $synthetic);
        }

        private function compileAlternatives(
            string $rule, array &$alternatives, array &$synthetic) : void {
            $priorities   = [];
            $associations = [];

            foreach ($alternatives as $aid => &$alternative) {
                $this->compileAlternative(
                    $rule, $aid, $alternative,
                    $synthetic, $priorities, $associations);
            }

            $this->compilePriorities($rule, $priorities, $associations);
            $this->compileAssociations($rule, $alternatives, $associations);
        }

        private function compileAlternative(
            string $rule, int $aid, Alternative $alternative,
            array &$synthetic, array &$priorities, array &$associations) : void {
            $this->compilePriority(
                $rule, $aid, $alternative,
                $priorities, $associations);
            $this->compileAssociation(
                $rule, $aid, $alternative,
                $priorities, $associations);
            $this->compileSymbols(
                $rule, $aid, $alternative, $synthetic);
        }

        private function compilePriority(
            string $rule, int $aid, Alternative $alternative,
            array &$priorities, array &$associations) : void {
            if ($aid > 0) {
                $prioritized = \count($priorities) > 0;
                if (($prioritized  && $alternative->priority === false) ||
                    (!$prioritized && $alternative->priority !== false)) {
                    throw Exception\Priority::inconsistent(
                        $this->file, $rule, $aid + 1);
                }
            }

            if ($alternative->priority !== false) {
                $priorities[$aid] = $alternative->priority;
            }
        }

        private function compilePriorities(
            string $rule, array $priorities, array $associations) : void {
            if (\count($priorities) === 1) {
                throw Exception\Priority::inert($this->file, $rule);
            }

            $seen = [];
            foreach ($priorities as $aid => $priority) {
                if (isset($seen[$priority])) {
                    // If the group has any explicit assoc, let compileAssociations
                    // validate it — a consistent assoc (e.g. all [left]) is valid.
                    $group = $associations[$priority] ?? [];
                    foreach ($group as $assoc) {
                        if ($assoc !== Associativity::NONE) {
                            continue 2;
                        }
                    }
                    throw Exception\Priority::ambiguous(
                        $this->file, $rule, $aid + 1);
                }
                $seen[$priority] = true;
            }
        }

        private function compileAssociation(
            string $rule, int $aid, Alternative $alternative,
            array &$priorities, array &$associations) : void {
            $assoc = $alternative->associativity;

            if ($assoc !== Associativity::NONE &&
                $alternative->priority === false) {
                throw Exception\Associativity::inconsistent(
                    $this->file, $rule, $aid + 1);
            }

            if ($alternative->priority !== false) {
                $associations[$alternative->priority][$aid] = $assoc;
            }
        }

        private function compileAssociations(
            string $rule, array $alternatives, array $associations) : void {
            foreach ($associations as $priority => $group) {
                if (\count($group) === 1) {
                    $aid = \array_key_first($group);
                    if ($group[$aid] !== Associativity::NONE) {
                        foreach ($alternatives[$aid]->symbols as $symbol) {
                            if ($symbol->name === $rule) {
                                continue 2;
                            }
                        }
                        throw Exception\Associativity::inert($this->file, $rule);
                    }
                } else {
                    $first = null;
                    foreach ($group as $aid => $assoc) {
                        if ($assoc === Associativity::NONE) {
                            throw Exception\Associativity::ambiguous(
                                $this->file, $rule, $aid + 1, 'missing');
                        }
                        if ($first === null) {
                            $first = $assoc;
                        } elseif ($assoc !== $first) {
                            throw Exception\Associativity::ambiguous(
                                $this->file, $rule, $aid + 1, 'conflict');
                        }
                    }
                }
            }
        }

        private function compileSymbols(string $rule, int $aid, Alternative $alternative, array &$synthetic) : void {
            foreach ($alternative->symbols as &$symbol) {
                $this->compileSymbol($rule, $symbol);

                if ($symbol->quantifier === Quantifier::NONE) {
                    continue;
                }

                $this->compileSynthetic(
                    $rule, $symbol, $synthetic);
            }
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
                        throw Exception\Undefined::symbol(
                            $this->file, $rule, $symbol->name);
                    }

                    $this->terminals[$symbol->name] = true;
            }

            $this->symbols[] = $symbol;
        }

        /* Rewrite quantified symbols into synthetic nullable/recursive rules,
         * replacing each quantified reference with the synthetic rule name.
         *
         *   A*  →  $A_star$ :  ε  |  ($A_star$ A)     (left-recursive)
         *   A+  →  $A_plus$ :  A  |  ($A_plus$ A)     (left-recursive)
         *   A?  →  $A_opt$  :  ε  |  A
         */
        private function compileSynthetic(string $rule, Symbol &$symbol, array &$synthetic) {
            $base  = $symbol->name;
            $name =
                \sprintf("\$%s_%s\$",
                    $base,
                    Quantifier::name($symbol->quantifier));

            if (!isset($synthetic[$name])) {
                $self = [
                    new Symbol(Token::IDENT, $name)];
                $one  = [
                    $this->symbols[] = new Symbol($symbol->type, $base)];

                $synthetic[$name] = match ($symbol->quantifier) {
                    Quantifier::STAR => [
                        Alternative::synthetic($this->file, [],                        Quantifier::STAR), /* ε */
                        Alternative::synthetic($this->file, \array_merge($self, $one), Quantifier::STAR), /* A* A */
                    ],
                    Quantifier::PLUS => [
                        Alternative::synthetic($this->file, $one,                      Quantifier::PLUS), /* A */
                        Alternative::synthetic($this->file, \array_merge($self, $one), Quantifier::PLUS), /* A+ A */
                    ],
                    Quantifier::OPTIONAL => [
                        Alternative::synthetic($this->file, [],                        Quantifier::OPTIONAL), /* ε */
                        Alternative::synthetic($this->file, $one,                      Quantifier::OPTIONAL), /* A */
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
            foreach ($this->terminals as $terminal => &$constant) {
                $constant = $this->lexer
                    ->config[$terminal]['const'];
            }

            foreach ($this->patterns as $pattern => &$constant) {
                $constant = $this->lexer
                    ->config[$pattern]['const'];
            }

            foreach ($this->symbols as $symbol) {
                $symbol->terminal =
                    $this->terminals[$symbol->name] ??
                    $this->patterns[$symbol->name]  ??
                    false;
            }
        }

        public function compile() : array {
            $this->compileDirectives();
            $this->compileRules();
            $this->compileTokens();
            $this->compileConstants();

            return [
                $this->start,
                $this->rules,
                $this->terminals,
                $this->patterns,
                $this->abstracts,
                $this->collector,
                $this->engine,
                $this->optimizations,
            ];
        }
    }
}
?>