<?php
namespace pharos\phathom\Grammar {
    final class Alternative {
        public function __construct(
            public              array     $symbols,
            public private(set) int|false $priority = false,
            public private(set) ?string   $action   = null,
        ) {}
    }
}
?>