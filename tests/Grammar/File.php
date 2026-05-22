<?php
namespace pharos\phathom\tests\Grammar {

    class File extends \pharos\phathom\Context {
        public function returnThings($alpha, $num) {
            $this->things[] = [$alpha, $num];
        }

        public function getThings() : array {
            return $this->things;
        }

        private array $things = [];
    }
}