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

namespace pharos\phathom\Exception {
    use \pharos\phathom\Token;

    final class Optimizer extends \pharos\phathom\Exception {
        public static function threw(string $optimization, Token|false $directive, \Throwable $thrown) : Optimizer {
            return new self(\sprintf(
                "while executing %s (%s) an uncaught exception (%s) was thrown",
                $optimization,
                $directive === false ?
                    // @codeCoverageIgnoreStart
                    \sprintf("builtin") :
                    // @codeCoverageIgnoreEnd
                    \sprintf("from %s:%d",
                        $directive->location['path'],
                        $directive->location['position']),
                \get_class($thrown),
            ), 0, $thrown);
        }
    }
}