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
    final class Buffer implements Interface\Buffer {
        public private(set) int $length;

        public function __construct(
            private string $source,
            public private(set) string $contents) {
                $this->length =
                    \strlen($contents);
        }

        public function __toString() : string {
            return $this->source;
        }

        public function __debugInfo() : array {
            return [
                'path' => (string) $this,
            ];
        }
    }
}
?>