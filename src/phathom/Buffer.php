<?php declare(strict_types=1);

namespace pharos\phathom {
    final class Buffer implements Interface\Buffer {
        public int $length;

        public function __construct(
            private string $source,
            public string $contents) {
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