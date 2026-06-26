<?php declare(strict_types=1);
/*
  +----------------------------------------------------------------------+
  | phathom                                                              |
  +----------------------------------------------------------------------+
  | Copyright (c) Joe Watkins 2026                                       |
  +----------------------------------------------------------------------+
  | This source file is subject to the BSD 3-Clause License bundled     |
  | with this package in the file LICENSE.                               |
  +----------------------------------------------------------------------+
  | Author: krakjoe                                                      |
  +----------------------------------------------------------------------+
 */

namespace pharos\phathom 
{
    use \pharos\phathom\Exception;

    final class File implements Interface\Buffer
    {
        const int REGULAR   = 1;
        const int DIRECTORY = 2;

        public private(set) string       $path;
        public private(set) int          $kind;
        private             string|false $buffer = false;
        private             int|false    $limit  = false;
        
        public string $contents {
            get {
                if ($this->buffer !== false) {
                    return $this->buffer;
                }

                // @codeCoverageIgnoreStart
                $this->buffer = @\file_get_contents($this->path);

                if ($this->buffer === false) {
                    throw new Exception\IO(
                        "{$this->path} cannot be read");
                }
                // @codeCoverageIgnoreEnd

                return $this->buffer;
            }
        }

        public int $length {
            get {
                if ($this->limit !== false) {
                    return $this->limit;
                }

                if ($this->buffer === false) {
                    $this->limit =
                        \strlen(
                            $this->contents);
                    return $this->limit;
                }

                $this->limit =
                    \strlen($this->buffer);
                return $this->limit;
            }
        }

        public function __construct(string $path) {
            $realpath = \realpath($path);

            if ($realpath === false) {
                throw new Exception\IO(
                    "{$path} cannot be found on the local filesystem");
            }

            $this->path = $realpath;

            if (\is_dir($this->path)) {
                $this->kind =
                    FILE::DIRECTORY;
            } else {
                $this->kind =
                    FILE::REGULAR;
            }
        }

        public function realpath(string $path) : string|false {
            return \realpath(\sprintf(
                "%s%s%s",
                $this->kind == FILE::DIRECTORY ?
                    $this->path : \dirname($this->path),
                \DIRECTORY_SEPARATOR,
                $path
            ));
        }

        public function relative(string $path) : self {
            return new self(\sprintf(
                "%s%s%s",
                $this->kind == FILE::DIRECTORY ?
                    $this->path : \dirname($this->path),
                \DIRECTORY_SEPARATOR,
                $path
            ));
        }

        public function writable() : bool {
            return \is_writable($this->path);
        }

        public function put(string $relative, string $contents) : self {
            if ($this->kind != FILE::DIRECTORY) {
                throw new Exception\IO(
                    "{$this->path} is not a directory");
            }

            if (!$this->writable()) {
                // @codeCoverageIgnoreStart
                throw new Exception\IO(
                    "{$this->path} is not writable");
                // @codeCoverageIgnoreEnd
            }

            $path = \sprintf(
                "%s%s%s",
                $this->path,
                \DIRECTORY_SEPARATOR,
                $relative);

            if (\file_put_contents($path, $contents) === false) {
                // @codeCoverageIgnoreStart
                throw new Exception\IO(
                    "cannot write {$path}, write failed");
                // @codeCoverageIgnoreEnd
            }

            return new self($path);
        }

        public function __serialize() : array {
            return [
                'path' => $this->path,
                'kind' => $this->kind
            ];
        }

        public function __unserialize(array $array) : void {
            foreach ($array as $member => $value) {
                $this->$member = $value;
            }
        }

        public function __debugInfo() : array {
            return [
                'path' => $this->path,
                'kind' => $this->kind,
            ];
        }

        public function __toString() : string {
            return $this->path;
        }
    }
}
?>