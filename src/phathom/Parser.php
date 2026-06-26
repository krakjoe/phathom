<?php declare(strict_types=1);
/*
  +----------------------------------------------------------------------+
  | phathom                                                              |
  +----------------------------------------------------------------------+
  | Copyright (c) Joe Watkins 2026                                       |
  +----------------------------------------------------------------------+
  | This source file is subject to the BSD 3-Clause License bundled     |
  | with this package in the file LICENSE.                               |
  +----------------------------------------------------------------------+
  | Author: krakjoe                                                      |
  +----------------------------------------------------------------------+
 */

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