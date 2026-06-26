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
            Engine $engine,
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