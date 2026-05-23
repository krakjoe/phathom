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
                    "{$path} cannot be found on the local filesystem");
            }

            $this->path = $realpath;
        }

        public function contents() : string {
            if ($this->buffer !== false) {
                return $this->buffer;
            }

            // @codeCoverageIgnoreStart
            if (!($handle = \fopen($this->path, "r"))) {
                throw new \Exception(
                    "{$this->path} cannot be opened for reading");
            }
            
            if (\flock($handle, \LOCK_SH) !== true) {
                throw new \Exception(
                    "{$this->path} cannot be locked for reading");
            }

            $this->buffer =
                \stream_get_contents(
                    $handle);

            if (\flock($handle, \LOCK_UN) !== true) {
                throw new \Exception(
                    "{$this->path} cannot be unlocked after reading");
            }

            \fclose($handle);

            if ($this->buffer === false) {
                throw new \Exception(
                    "{$this->path} cannot be read");
            }
            // @codeCoverageIgnoreEnd

            return $this->buffer;            
        }

        public function relative(string $path) : self {
            return new self(\sprintf(
                "%s%s%s",
                \dirname($this->path),
                \DIRECTORY_SEPARATOR,
                $path
            ));
        }

        public function writable() : bool {
            return \is_writable($this->path);
        }

        public function put(string $relative, string $contents) : self {
            if (!\is_dir($this->path)) {
                throw new \Exception(
                    "{$this->path} is not a directory");
            }

            if (!$this->writable()) {
                // @codeCoverageIgnoreStart
                throw new \Exception(
                    "{$this->path} is not writable");
                // @codeCoverageIgnoreEnd
            }

            $path = \sprintf(
                "%s%s%s",
                $this->path,
                \DIRECTORY_SEPARATOR,
                $relative);

            $handle =
                \fopen(
                    $path, 
                "w");

            if ($handle === false) {
                // @codeCoverageIgnoreStart
                throw new \Exception(
                    "cannot open {$path} for writing");
                // @codeCoverageIgnoreEnd
            }

            if (\flock($handle, \LOCK_EX, $blocking) !== true) {
                // @codeCoverageIgnoreStart
                throw new \Exception(
                    "cannot lock {$path} for writing");
                // @codeCoverageIgnoreEnd
            }

            if (\fwrite($handle, $contents) != \strlen($contents)) {
                // @codeCoverageIgnoreStart
                throw new \Exception(
                    "cannot write {$path}, write failed");
                // @codeCoverageIgnoreEnd
            }

            \flock($handle, \LOCK_UN);
            \fclose($handle);

            return new self($path);
        }

        public function __toString() : string {
            return $this->path;
        }
    }
}