<?php
namespace pharos\phathom\tests\Grammar\Directive\Optimizer {

    final class Throws extends \pharos\phathom\Grammar\Optimization {
        public function pass(bool $generated) : bool {
            throw new \Exception();
        }
    }
}