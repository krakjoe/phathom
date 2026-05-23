<?php
namespace pharos\phathom\tests\Lock {
    use \pharos\phathom\File;
    use \pharos\phathom\Lock;

    class Test extends \PHPUnit\Framework\TestCase {
        private ?File $directory;

        public function setUp() : void {
            \touch(\sprintf(
                "%s%s.guard",
                \sys_get_temp_dir(),
                \DIRECTORY_SEPARATOR));
            $this->directory = new File(\sys_get_temp_dir());
        }

        public function tearDown() : void {
            \unlink(\sprintf(
                "%s%s.guard",
                \sys_get_temp_dir(),
                \DIRECTORY_SEPARATOR));
        }

        public function testNonDirectory() : void { 
            $dummy = \sprintf(
                "%s%s.dummy",
                \sys_get_temp_dir(),
                \DIRECTORY_SEPARATOR);
            \touch($dummy);

            $this->expectException(\pharos\phathom\Exception\IO::class);
            $this->expectExceptionMessageMatches(
                "/attempt to create guard for non-directory .* failed/");

            new Lock(new File($dummy));
        }

        public function testAcquire() : void {
            $lock = new Lock($this->directory);
            $lock->acquire();

            $this->expectException(\pharos\phathom\Exception\IO::class);
            $this->expectExceptionMessageMatches(
                "/attempt to acquire lock for .* that was already acquired/");

            try {
                $lock->acquire();
            } finally {
                $lock->release();
            }
        }

        public function testRelease() : void {
            $lock = new Lock($this->directory);
            
            $this->expectException(\pharos\phathom\Exception\IO::class);
            $this->expectExceptionMessageMatches(
                "/attempt to release lock for .* that isn't currently acquired/");

            $lock->release();
        }
    }
}
?>