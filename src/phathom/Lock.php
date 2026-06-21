<?php declare(strict_types=1);

namespace pharos\phathom {
    use \pharos\phathom\Exception;

    final class Lock {
        public private(set)
            bool $locked = false;
        private File $guard;
        private      $handle;

        public function __construct(
            public private(set) File $directory) {
            if ($this->directory->kind != FILE::DIRECTORY) {
                throw new Exception\IO(
                    "attempt to create guard for non-directory ".
                    "{$this->directory} ".
                    "failed");
            }

            $this->guard =
                $this->directory
                    ->relative(".guard");
            $this->handle = \fopen(
                (string) $this->guard, "r+");
            if (!\is_resource($this->handle)) {
                // @codeCoverageIgnoreStart
                throw new Exception\IO(
                    "attempt to open ".
                    "{$this->directory}/.guard ".
                    "failed");
                // @codeCoverageIgnoreEnd
            }
        }

        public function acquire() : void {
            if ($this->locked) {
                throw new Exception\IO(
                    "attempt to acquire lock for ".
                    "{$this->directory} ".
                    "that was already acquired");
            }

            if (\flock(
                    $this->handle, \LOCK_EX) === false) {
                // @codeCoverageIgnoreStart
                throw new Exception\IO(
                    "attempt to acquire lock for ".
                    "{$this->directory} ".
                    "failed");
                // @codeCoverageIgnoreEnd
            }

            $this->locked = true;
        }

        public function release() : void {
            if (!$this->locked) {
                throw new Exception\IO(
                    "attempt to release lock for ".
                    "{$this->directory} ".
                    "that isn't currently acquired");
            }

            if (\flock($this->handle, \LOCK_UN) === false) {
                // @codeCoverageIgnoreStart
                throw new Exception\IO(
                    "attempt to release lock for ".
                    "{$this->directory} ".
                    "failed");
                // @codeCoverageIgnoreEnd
            }

            $this->locked = false;
        }

        public function __destruct() {
            \fclose($this->handle);
        }
    }
}
?>