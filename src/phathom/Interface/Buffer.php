<?php
namespace pharos\phathom\Interface {
    /**
    * !This is an internal interface!
    **/
    interface Buffer {
        public function contents() : string;

        public function __debugInfo() : array;

        public function __toString() : string;
    }
}
?>