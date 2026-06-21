<?php declare(strict_types=1);

namespace pharos\phathom\Grammar\Interface {
    use \pharos\phathom\Grammar;
    use \pharos\phathom\Context;
    use \pharos\phathom\File;
    use \pharos\phathom\Buffer;

    interface Engine {
        public Automaton $automaton     { get; }
        public array     $optimizations { get; }

        public function __construct(Grammar $grammar);

        public function __invoke(
            Context $context, File|Buffer $input) : mixed;
    }
}
?>