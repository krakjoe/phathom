<?php
namespace pharos\phathom 
{
    final class File
    {
        public private(set) string $path;
        private             string|false $buffer = false;

        public function __construct(string $path) {
            $realpath = \realpath($path);

            if ($realpath === false) {
                throw new \Exception(
                    "$path cannot be found on the local filesystem");
            }

            $this->path = $realpath;
        }

        public function contents() : string {
            if ($this->buffer !== false) {
                return $this->buffer;
            }

            $buffer =
                \file_get_contents($this->path);

            if ($buffer === false) {
                // @codeCoverageIgnoreStart
                throw new \Exception(
                    "Failed to read file: $this->path");
                // @codeCoverageIgnoreEnd
            }

            return $this->buffer = $buffer;
        }

        public function relative(string $path) : File {
            return new self(\sprintf(
                "%s%s%s",
                \dirname($this->path),
                \DIRECTORY_SEPARATOR,
                $path
            ));
        }

        public function __toString() : string {
            return $this->path;
        }
    }
}