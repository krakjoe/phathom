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
    use \pharos\phathom\Exception;

    final class Annotation {
        public static function factory(
            array $annotations, Token $value) : Interface\Annotation {
            foreach ($annotations as $annotation) {
                if ($annotation::match($value)) {
                    return new $annotation($value);
                }
            }

            throw Exception\Annotation::unknown($value, $annotations);
        }
    }
}
?>