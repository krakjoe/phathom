<?php
namespace pharos\phathom\tests\Grammar\Sequence;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testSequence() : void {
        $file = $this->file
            ->relative("Sequence.grammar");
        $content = $this->file
            ->relative("Sequence.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->assertSame($parser->parse(), [
            ['one' => 'one'],
            ['two' => 'two'],
            ['three' => 'three'],
        ]);
    }
}