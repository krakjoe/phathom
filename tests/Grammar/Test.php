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
            \pharos\phathom\Exception\Undefined::class);
        $this->expectExceptionMessageMatches(
            "/grammar must contain at least one rule/");

        new \pharos\phathom\Grammar($file);
    }

    public function testUndefinedSymbol() : void {
        $file = $this->file
            ->relative("UndefinedSymbol.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Undefined::class);
        $this->expectExceptionMessageMatches(
            "/Undefined symbol 'undefined'/");

        new \pharos\phathom\Grammar($file);
    }

    public function testUndefinedVariable() : void {
        $file = $this->file
            ->relative("UndefinedVariable.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Undefined::class);
        $this->expectExceptionMessageMatches(
            "/Undefined variable '\\$2'/");

        new \pharos\phathom\Grammar($file);
    }
}
