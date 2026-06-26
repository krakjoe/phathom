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

namespace pharos\phathom\Earley {
    use \pharos\phathom\Grammar;
    use \pharos\phathom\Context;
    use \pharos\phathom\File;
    use \pharos\phathom\Buffer;

    use \pharos\phathom\Grammar\Interface;

    final class Engine implements Interface\Engine {
        public private(set) Interface\Automaton $automaton;
        public private(set) array               $optimizations = [];

        public function __construct(
            private Grammar $grammar) {
            $this->automaton =
                new Automaton($grammar);
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