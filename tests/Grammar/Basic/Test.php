<?php
namespace pharos\phathom\tests\Grammar\Basic;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testBasic() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $content = $this->file
            ->relative("Basic.content");

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

        $this->assertInstanceOf(
            \pharos\phathom\Grammar::class, $parser->grammar);
    }

    public function testBasicDefault() : void {
        $file = $this->file
            ->relative("BasicDefault.grammar");
        $content = $this->file
            ->relative("Basic.content");

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

    public function testBasicNomatch() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $content = $this->file
            ->relative("BasicNomatch.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessageMatches(
            "/Unexpected character \";\"/");

        $parser->parse();
    }

    public function testBasicNoparse() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $content = $this->file
            ->relative("BasicNoparse.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->expectException(
            \pharos\phathom\Exception\Execute::class);
        $this->expectExceptionMessageMatches(
            "/does not match 'unit'/");

        $parser->parse();
    }

    public function testBasicUnrecognizedDirective() : void {
        $file = $this->file
            ->relative("BasicUnrecognizedDirective.grammar");
        
        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected directive, expected ".
                "lexer, context, or include, ".
            "got IDENT(unrecognized)");

        new \pharos\phathom\Grammar($file);
    }
}