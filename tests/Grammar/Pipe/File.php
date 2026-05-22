<?php
namespace pharos\phathom\tests\Grammar\Pipe {

    class File extends \pharos\phathom\Context {

        public function section($name) : mixed {
            $this->section = $name;
            $this->things[
                $this->section
            ] = [];
            return $name;
        }

        public function item($key, $value) : mixed {
            if ($this->section) {
                $this->things[
                    $this->section
                ][$key] = $value;
            } else {
                $this->things[$key] = $value;
            }

            return [$key => $value];
        }

        public function getThings() {
            return $this->things;
        }

        private ?string $section = null;
        private array $things = [];
    }
}
?>