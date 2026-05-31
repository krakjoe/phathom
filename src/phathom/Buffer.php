<?php
namespace pharos\phathom {
    final class Buffer implements Interface\Buffer {
        public function __construct(
            private string $source,
            private string $contents) {}

        public function __toString() : string {
            return $this->source;
        }

        public function __debugInfo() : array {
            return [
                'path' => (string) $this,
            ];
        }

        public function contents() : string {
            return $this->contents;
        }
    }
}
?>