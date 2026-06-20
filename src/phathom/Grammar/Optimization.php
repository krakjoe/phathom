<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    abstract class Optimization implements Interface\Optimization {
        /* 
            Compiler is sealed, unsealing for the purposes of optimization passes
            makes no sense, so instead Optimizations must accept a deconstruction
            of Compiler.
        */
        final public function __construct(
            protected \pharos\phathom\Lexer  $lexer,
            protected                 string $start,
            protected                 array  $rules,
            protected                 array  $terminals,
            protected                 array  $patterns,
            protected                 array  $abstracts
        ) {}

        abstract public function pass() : void;

        /* Contractually obliged to return reconstruction of Compiler after pass is executed */
        final public function reconstruct() : array {
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
?>