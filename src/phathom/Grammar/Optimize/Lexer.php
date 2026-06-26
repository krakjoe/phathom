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

namespace pharos\phathom\Grammar\Optimize {
    use \pharos\phathom\Grammar\Optimization;
    use \pharos\phathom\Grammar\Symbol;

    /*
    * We're going to prewarm the pattern cache on lexer by telling
    * it what it may expect at any call to scan for this grammar.
    * This achieves:
    *   no regex pattern allocations (in userland) during a parse
    *   the pattern cache is serialized complete with the grammar
    */
    final class Lexer extends Optimization {
        public function pass(bool $generated) : bool {
            if ($generated === true) {
                /* don't take part in post-generate pass */
                return false;
            }

            foreach ($this->engine->automaton->expected() as $expected) {
                $this->lexer->expect($expected);
            }

            /* nothing to commit */
            return false;
        }
    }
}