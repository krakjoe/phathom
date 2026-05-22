<?php
namespace pharos\phathom {
    class Context {
        public function __construct(
            public private(set) Parser $parser) {}
    }
}