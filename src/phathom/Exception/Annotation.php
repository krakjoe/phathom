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
    use \pharos\phathom\Grammar\Token;

    final class Annotation extends \pharos\phathom\Exception {
        public static function explain(array $annotations, string $condition = 'or') : string {
            $expected = [];

            foreach ($annotations as $annotation) {
                $expected[] = \sprintf(
                    "%s (%s) expected %s",
                    $annotation,
                    $annotation::name(),
                    $annotation::expect(),
                );
            }

            return parent::explain($expected, $condition);
        }

        public static function unknown(Token $directive, array $annotations) : Annotation {
            return new self(\sprintf(
                "Unknown annotation, %s, ".
                "got %s",
                self::explain($annotations, 'and'),
                $directive::print($directive)));
        }
    }
}