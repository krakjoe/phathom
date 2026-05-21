<?php
namespace pharos\phathom\tests\Grammar\Quantifiers\Asterix;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testAsterixZero() : void {
        $file = $this->file
            ->relative("Asterix.grammar");
        $content = $this->file
            ->relative("AsterixZero.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);
        $result  = $parser->parse();

        $this->assertSame($result->getThings(), []);
    }

    public function testAsterixOne() : void {
        $file = $this->file
            ->relative("Asterix.grammar");
        $content = $this->file
            ->relative("AsterixOne.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);
        $result  = $parser->parse();

        $this->assertSame($result->getThings(), [
            0 => [
                0 => "one",
                1 => "42"
            ]  
        ]);
    }

    public function testAsterixMore() : void {
        $file = $this->file
            ->relative("Asterix.grammar");
        $content = $this->file
            ->relative("AsterixMore.content");
        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);
        $result  = $parser->parse();

        $this->assertSame($result->getThings(), [
            0 => [
                0 => "one",
                1 => "42"
            ], 
            1 => [
                0 => "two",
                1 => "24"
            ]
        ]);
    }
}