<?php
namespace pharos\phathom\tests\Grammar\Directive\Include;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testInclude() : void {        
        $file = $this->file
            ->relative("Include.grammar");
        $content = $this->file
            ->relative("Include.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->assertSame($parser->parse(), [
            "one" => "42",
        ]);
    }

    public function testDuplicateError() : void {
        $file = $this->file
            ->relative("Duplicate.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessageMatches(
            "/Unexpected duplicate include at .*:63, ".
            ".*Snippet\.grammar already included at .*:35/");

        new \pharos\phathom\Grammar($file);    
    }

    public function testRecursionError() : void {
        $file = $this->file
            ->relative("Recursion.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessageMatches(
            "/Unexpected duplicate include at .*:35, ".
            ".*Recursion.grammar already included at .*Recursion.grammar:0/");

        new \pharos\phathom\Grammar($file);  
    }
}