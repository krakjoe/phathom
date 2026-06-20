<?php declare(strict_types=1);

namespace pharos\phathom\Grammar\Interface {
    use \pharos\phathom\Lexer;

    /**
     * !THIS IS AN INTERNAL INTERFACE!
     * 
     * An interface does not provide a strong enough contract for implementations
     * 
     * See abstract \pharos\phathom\Grammar\Optimization for details
     * 
     * !THIS IS AN INTERNAL INTERFACE!
     */
    interface Optimization {
        public function __construct(
            Lexer  $lexer,
            string $start,
            array  $rules,
            array  $terminals,
            array  $patterns,
            array  $literals,
            array  $symbols);

        public function pass(bool $generated) : bool;

        public function reconstruct() : array;
    }
}
?>