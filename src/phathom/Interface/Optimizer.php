<?php
namespace pharos\phathom\Interface {
    use \pharos\phathom\Lexer;

    interface Optimizer {
        /* 
            Compiler and Grammar are sealed, unsealing them for the purposes of
            optimization passes makes no sense, so instead Optimizers must accept
            a deconstruction of the Compiler.
        */
        public function __construct(
            Lexer  $lexer,
            string $start,
            array  $rules,
            array  $terminals,
            array  $patterns,
            array  $abstracts);

        /* Expected to return reconstruction that mirrors constructor exactly */
        public function __invoke() : array;
    }
}
?>