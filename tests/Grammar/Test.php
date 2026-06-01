<?php
namespace pharos\phathom\tests\Grammar;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testUnlexed() : void {
        $file = $this->file
            ->relative("Unlexed.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Undefined::class);
        $this->expectExceptionMessageMatches(
            "/Undefined symbol/");

        new \pharos\phathom\Grammar($file);
    }

    public function testUnruled() : void {
        $file = $this->file
            ->relative("Unruled.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Undeclared::class);
        $this->expectExceptionMessageMatches(
            "/does not declare any rules/");

        new \pharos\phathom\Grammar($file);
    }

    public function testUndefined() : void {
        $file = $this->file
            ->relative("Undefined.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Undefined::class);
        $this->expectExceptionMessageMatches(
            "/Undefined symbol 'undefined'/");

        new \pharos\phathom\Grammar($file);
    }
}
