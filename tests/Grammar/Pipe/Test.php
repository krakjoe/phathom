<?php
namespace pharos\phathom\tests\Grammar\Pipe;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testPipe() : void {
        $file = $this->file
            ->relative("Pipe.grammar");
        $content = $this->file
            ->relative("Pipe.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->assertSame($parser->parse(), [
            "section" => [
                "item" => "value"
            ]
        ]);
    }
}