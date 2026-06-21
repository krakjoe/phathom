<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    use \pharos\phathom\Lexer;

    abstract class Optimization implements Interface\Optimization {
        /* 
            Grammar is sealed, unsealing for the purposes of optimization passes
            makes no sense, so instead Optimizations must accept a deconstruction
            of Grammar.

            We didn't use references here because spooky action at a distance is spooky.

            In addition, it is convenient if Optimizations are speculative (easier to write).
        */
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

        /*
        * $generated shall be true on pass two (ie, post generation)
        * 
        * return true to commit changes
        */
        abstract public function pass(bool $generated) : bool;

        /* Contractually obliged to return reconstruction of Grammar where pass commits */
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