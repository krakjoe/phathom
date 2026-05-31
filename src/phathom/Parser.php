<?php

namespace pharos\phathom 
{
    use \pharos\phathom\Exception\Serial as SerializationException;

    final class Parser 
    {
        public function __construct(
            public private(set) Grammar      $grammar,
            public private(set) File|Buffer  $input) {
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