<?php declare(strict_types=1);

namespace pharos\phathom {
    class Context {
        public function __construct(
            public private(set) Grammar $grammar) {}
    }
}
?>