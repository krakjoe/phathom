<?php declare(strict_types=1);

namespace pharos\phathom\GLR {
    use \pharos\phathom\Context;
    use \pharos\phathom\File;
    use \pharos\phathom\Buffer;
    use \pharos\phathom\Grammar;

    use \pharos\phathom\Grammar\Interface;

    final class Engine implements Interface\Engine {
        public private(set) Interface\Automaton $automaton;
        public private(set) array               $optimizations = [];

        public function __construct(private Grammar $grammar) {
            $this->automaton = new Automaton($grammar);
        }

        public function __invoke(Context $context, File|Buffer $input) : mixed {
            $chart =
                new Chart(
                    $this->grammar, $input);
            $evaluator =
                new Evaluator(
                    $chart, $context);
            return $evaluator();
        }
    }
}
?>
