<?php
namespace pharos\phathom\tests\Grammar {
    abstract class Token extends \pharos\phathom\Token {

        public function custom() : bool {
            return true;
        }
    }
}
?>