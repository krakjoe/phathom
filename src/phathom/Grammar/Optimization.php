<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    use \pharos\phathom\Lexer;

    abstract class Optimization implements Interface\Optimization {
        final public function __construct(
            protected Interface\Engine  $engine,
            protected Lexer             $lexer,
            protected            string $start,
            protected            array  $rules,
            protected            array  $terminals,
            protected            array  $patterns,
            protected            array  $literals,
            protected            array  $symbols
        ) {}

        abstract public function pass(bool $generated) : bool;

        final public function reconstruct() : array {
            return [
                $this->lexer,
                $this->start,
                $this->rules,
                $this->terminals,
                $this->patterns,
                $this->literals,
            ];
        }
    }
}
?>