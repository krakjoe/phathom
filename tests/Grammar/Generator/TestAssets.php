<?php
namespace pharos\phathom\tests\Grammar\Generator {
    class TestAssets extends \PHPUnit\Framework\TestCase {
        public function testExpectations() : void {
            $symbols = [];
            $generator =
                new \pharos\phathom\Grammar\Generator(
                    null, "\stdClass", []);
            $symbols[] = (string) $generator;
            $symbols[] = (string) $generator;
            $this->assertSame(...$symbols);
        }
    }
}