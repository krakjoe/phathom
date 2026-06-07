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
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->assertSame($parser->parse($content), [
            "one" => "42",
        ]);
    }

    public function testDuplicateError() : void {
        $file = $this->file
            ->relative("Duplicate.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/include for Snippet.grammar ".
                "at .*Duplicate.grammar:63, ".
            "already included at .*Duplicate.grammar:35/");

        new \pharos\phathom\Grammar($file);    
    }

    public function testRecursionError() : void {
        $file = $this->file
            ->relative("Recursion.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/include for Recursion.grammar ".
                "at .*Recursion.grammar:35, ".
            "already included at .*Recursion.grammar:0/");

        new \pharos\phathom\Grammar($file);  
    }

    public function testReserved() : void {
        $file = $this->file
            ->relative("Reserved.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/include cannot be used as a rule name; ".
                "token, context, lexer, and include ".
            "are reserved for directives, ".
            "got IDENT\(include\) at .*Reserved.grammar:0/");

        new \pharos\phathom\Grammar($file);
    }
}