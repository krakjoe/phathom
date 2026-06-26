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

namespace pharos\phathom\Grammar\Annotation {
    use \pharos\phathom\Grammar\Interface\Annotation as AnnotationInterface;

    final class Priority implements AnnotationInterface {
        public private(set) int $value;

        public function __construct(
            \pharos\phathom\Grammar\Token $token) {
            $this->value = (int) (string) $token;
        }

        public static function name()   : string { return "priority"; }
        public static function expect() : string { return '/^[0-9]+$/'; }

        public static function match(
            \pharos\phathom\Grammar\Token $token) : bool {
            return (bool) \preg_match(
                '/^[0-9]+$/', (string) $token);
        }
    }
}
?>