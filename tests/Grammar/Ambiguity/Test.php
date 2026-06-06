<?php
namespace pharos\phathom\tests\Grammar\Ambiguity;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testRoot() : void {
        $file = $this->file
            ->relative("Root.grammar");
        $content = $this->file
            ->relative("Ambiguity.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar);

        $this->expectException(
            \pharos\phathom\Exception\Ambiguity::class);
        $this->expectExceptionMessageMatches('/ambiguity.*spanning/');
        $parser->parse($content);
    }

    public function testRule() : void {
        $file = $this->file
            ->relative("Rule.grammar");
        $content = $this->file
            ->relative("Ambiguity.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar);

        $this->expectException(
            \pharos\phathom\Exception\Ambiguity::class);
        $this->expectExceptionMessageMatches('/ambiguity.*spanning/');
        $parser->parse($content);
    }

    public function testNullable() : void {
        $file = $this->file
            ->relative("Nullable.grammar");
        $content = new \pharos\phathom\Buffer(
            "empty", "");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->expectException(
            \pharos\phathom\Exception\Ambiguity::class);
        $this->expectExceptionMessageMatches(
            '/ambiguity.*matching no tokens/s');
        $parser->parse($content);
    }
}