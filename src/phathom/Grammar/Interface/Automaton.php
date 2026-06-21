<?php
namespace pharos\phathom\Grammar\Interface {
    use \pharos\phathom\Grammar;

    interface Automaton {
        public function __construct(Grammar $grammar);

        public function expected() : array;
    }
}