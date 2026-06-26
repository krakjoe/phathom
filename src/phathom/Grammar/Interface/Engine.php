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