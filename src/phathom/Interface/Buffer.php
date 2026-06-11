<?php declare(strict_types=1);

namespace pharos\phathom\Interface {
    /**
    * !This is an internal interface!
    **/
    interface Buffer {
        public string $contents { get; }
        public int    $length   { get; }

        public function __debugInfo() : array;

        public function __toString() : string;
    }
}
?>