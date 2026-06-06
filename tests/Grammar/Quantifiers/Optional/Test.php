<?php
namespace pharos\phathom\tests\Grammar\Quantifiers\Optional;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testOptionalZero() : void {
        $file = $this->file
            ->relative("Optional.grammar");
        $content = $this->file
            ->relative("OptionalZero.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->assertSame($parser->parse($content), []);
    }

    public function testOptionalOne() : void {
        $file = $this->file
            ->relative("Optional.grammar");
        $content = $this->file
            ->relative("OptionalOne.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->assertSame($parser->parse($content),[
            "one" => "42",
        ]);
    }
}