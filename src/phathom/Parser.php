<?php
namespace pharos\phathom
{
    final class Parser
    {
        public private(set) Context  $context;
               private      \Closure $parser;

        public function __construct(Grammar $grammar) {
            $this->context =
                $grammar->factory();
            $this->parser =
                $grammar->execute(...);
        }

        public function parse(File|Buffer $input) : mixed {
            return ($this->parser)(
                $this->context, $input);
        }
    }
}
?>