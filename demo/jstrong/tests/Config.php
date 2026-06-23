<?php

namespace pharos\phathom\demo\jstrong\tests {
    class Config {
        public private(set) mixed $property;

        /* used in test case, not parser */
        public function __construct(array $config) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}
?>