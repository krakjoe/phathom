<?php
namespace pharos\phathom {
    use \pharos\phathom\Exception\IO as IOException;

    final class Assets {
        private Lock $lock;

        public function __construct(
            public private(set) File $directory) {
            $this->lock =
                new Lock($directory);
        }

        public function entry(string $symbol, \Closure $generate) : File {
            $this->lock
                ->acquire();

            try {
                try {
                    $result = $this->directory
                        ->relative(
                            \sprintf(
                                "%s/%s.php",
                                $this->directory,
                                $symbol));
                } catch (IOException $ex) {
                    $result = $this->directory
                        ->put(\sprintf(
                            "%s.php", $symbol),
                            $generate());
                }
            } finally {
                $this->lock
                    ->release();
            }
            return $result;
        }
    }
}
?>