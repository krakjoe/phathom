<?php
namespace pharos\phathom\tests\Grammar\Directive\Optimizer {
    use \pharos\phathom\Lexer;
    use \pharos\phathom\Interface\Optimizer;

    final class Throws implements Optimizer {
        public function __construct(
            private Lexer  $lexer,
            private string $start,
            private array  $rules,
            private array  $terminals,
            private array  $patterns,
            private array  $abstracts
        ) {}

        public function __invoke() : array {
            throw new \Exception();

            return [
                $this->lexer,
                $this->start,
                $this->rules,
                $this->terminals,
                $this->patterns,
                $this->abstracts
            ];
        }
    }
}