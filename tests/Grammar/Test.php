<?php
namespace pharos\phathom\tests\Grammar;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testUnruled() : void {
        $file = $this->file
            ->relative("Unruled.grammar");

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(
            "/does not contain any rules/");

        new \pharos\phathom\Grammar($file);
    }

    public function testUnlexed() : void {
        $file = $this->file
            ->relative("Unlexed.grammar");

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(
            "/does not declare a lexer/");

        new \pharos\phathom\Grammar($file);
    }

    public function testUnknownSymbol() : void {
        $file = $this->file
            ->relative("UnknownSymbol.grammar");

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(
            "/Unknown symbol 'undefined'/");

        new \pharos\phathom\Grammar($file);
    }
}
