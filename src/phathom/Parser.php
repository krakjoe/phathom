<?php

namespace pharos\phathom 
{
    final class Parser 
    {
        private Grammar $grammar;
        private File    $file;

        public function __construct(Grammar $grammar, File $file) {
            $this->grammar = $grammar;
            $this->file    = $file;
        }

        public function parse() : Node {
            $this->file->buffer();

            $node =
                $this->grammar
                    ->factory($this);

            return $this->grammar
                ->execute($this, $node);
        }

        public function getGrammar() : Grammar {
            return $this->grammar;
        }

        public function getFile(): File {
            return $this->file;
        }
    }
}