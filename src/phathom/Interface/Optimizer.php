<?php declare(strict_types=1);

namespace pharos\phathom\Interface {
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
    interface Optimizer {
        public function __construct(
            Lexer  $lexer,
            string $start,
            array  $rules,
            array  $terminals,
            array  $patterns,
            array  $abstracts);

        public function pass() : void;

        public function reconstruct() : array;
    }
}
?>