<?php
namespace pharos\phathom\tests\Grammar\Pipe {

    class File extends \pharos\phathom\Node {

        public function section($name) : void {
            $this->section = $name;
            $this->things[
                $this->section
            ] = [];
        }

        public function item($key, $value) : void {
            if ($this->section) {
                $this->things[
                    $this->section
                ][$key] = $value;
            } else {
                $this->things[$key] = $value;
            }
        }

        public function getThings() {
            return $this->things;
        }

        private ?string $section = null;
        private array $things = [];
    }
}
?>