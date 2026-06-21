<?php declare(strict_types=1);

namespace pharos\phathom {
    use \pharos\phathom\Exception;

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
                } catch (Exception\IO $ex) {
                    $result = $this->directory
                        ->put($file, $generate());
                }
            } finally {
                $this->lock
                    ->release();
            }
            return $result;
        }

        public function __serialize() : array {
            return [
                'directory' => $this->directory,
            ];
        }

        public function __unserialize(array $array) : void {
            foreach ($array as $member => $value) {
                $this->$member = $value;
            }

            $this->lock = new Lock($this->directory);
        }
    }
}
?>