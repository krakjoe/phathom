<?php
namespace pharos\phathom\tests\Grammar\Quantifiers\Plus;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testPlusOne() : void {
        $file = $this->file
            ->relative("Plus.grammar");
        $content = $this->file
            ->relative("PlusOne.content");
    
        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->assertSame($parser->parse(), [
            0 => ["two" => "24"],
        ]);
    }

    public function testPlusMore() : void {
        $file = $this->file
            ->relative("Plus.grammar");
        $content = $this->file
            ->relative("PlusMore.content");
        
        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->assertSame($parser->parse(), [
            0 => ["one" => "42"], 
            1 => ["two" => "24"],
        ]);
    }
}