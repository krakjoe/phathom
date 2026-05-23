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
                $file = \sprintf("%s.php", $symbol);
                try {
                    $result = $this->directory
                        ->relative($file);
                } catch (IOException $ex) {
                    $result = $this->directory
                        ->put($file, $generate());
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