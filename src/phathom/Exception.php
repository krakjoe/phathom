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

namespace pharos\phathom {
    class Exception extends \Exception {
        public static function explain(array $options, string $condition = 'or') : string {
            switch (\count($options)) {
                case 0:
                    return "end of input";

                case 1:
                    return $options[0];

                case 2:
                    return \vsprintf(
                        "%s {$condition} %s", $options);

                default:
                    $last =
                        \array_pop($options);
                    return \sprintf(
                        "%s, {$condition} %s",
                        \implode(", ", $options),
                        $last);
            }
        }
    }
}
?>