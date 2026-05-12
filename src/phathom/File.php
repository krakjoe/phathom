<?php
namespace pharos\phathom 
{
    final class File
    {
        private string|false $path   = false;
        private string|false $buffer = false;

        public function __construct(string $path) {
            if (!\file_exists($path)) {
                throw new \Exception(
                    "$path does not exist");
            }

            $this->path = $path;
        }

        public function buffer() : void {
            if ($this->buffer !== false) {
                return;
            }

            $this->buffer =
                @\file_get_contents($this->path);

            if ($this->buffer === false) {
                throw new \Exception(
                    "Failed to read file: $this->path");
            }
        }

        public function buffered() : bool {
            return $this->buffer !== false;
        }

        public function getBuffer(): string|false {
            if (!$this->buffered()) {
                $this->buffer();
            }

            return $this->buffer;
        }

        public function getPath() : string|false {
            return $this->path;
        }
    }
}