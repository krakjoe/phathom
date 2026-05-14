<?php
namespace pharos\phathom\tests\Grammar\Unexpected;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testFirst() : void {
        $first = \sprintf(
            "%s%sFirst.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected COLON in rule 'unit'");

        new \pharos\phathom\Grammar($first);
    }

    public function testSecond() : void {
        $second = \sprintf(
            "%s%sSecond.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Expected COLON, got EOF near token 4");

        new \pharos\phathom\Grammar($second);
    }

    public function testThird() : void {
        $third = \sprintf(
            "%s%sThird.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected ! expected IDENT");

        new \pharos\phathom\Grammar($third);
    }

    public function testFourth() : void {
        $fourth = \sprintf(
            "%s%sFourth.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected ! at character 4, expected IDENT");

        new \pharos\phathom\Grammar($fourth);
    }
}