<?php
namespace pharos\phathom\tests\Grammar\Unexpected;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testSecond() : void {
        $second = \sprintf(
            "%s%sSecond.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Expected COLON, got EOF near token");

        new \pharos\phathom\Grammar($second);
    }
}