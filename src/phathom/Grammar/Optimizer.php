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

namespace pharos\phathom\Grammar {
    use \pharos\phathom\Lexer;
    use \pharos\phathom\Exception;
    
    final class Optimizer {
        public function __construct(
            private Interface\Engine $engine,
            private Lexer  $lexer,
            private string $start,
            private array  $rules,
            private array  $terminals,
            private array  $patterns,
            private array  $literals,
            private array  $symbols,
        ) {}

        public function optimize(array $optimizations, bool $generated) : array {            
            foreach ($optimizations as $optimization => $directive) {
                $optimizer =
                    new $optimization(
                        $this->engine,
                        $this->lexer,
                        $this->start,
                        $this->rules,
                        $this->terminals,
                        $this->patterns,
                        $this->literals,
                        $this->symbols);

                try {
                    $commit =
                        $optimizer->pass($generated);
                } catch(\Throwable $thrown) {
                    throw Exception\Optimizer::threw(
                        $optimization,
                        $directive,
                        $thrown);
                }

                if ($commit === true) [
                    $this->lexer,
                    $this->start,
                    $this->rules,
                    $this->terminals,
                    $this->patterns,
                    $this->literals,
                ] = $optimizer->reconstruct();
            }

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