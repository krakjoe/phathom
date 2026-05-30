<?php

namespace pharos\phathom 
{
    final class Parser 
    {
        public function __construct(
            public private(set) Grammar $grammar,
            public private(set) File    $file) {
        }

        public function parse() : Context {
            $context =
                $this->grammar
                    ->factory($this);

            return $this->grammar
                ->execute($context);
        }
    }
}
?>