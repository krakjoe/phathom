<?php
namespace pharos\phathom\tests\Grammar\Directive\Lexer;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testDuplicate() : void {
        $file = $this->file
            ->relative("Duplicate.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/already loaded/");

        new \pharos\phathom\Grammar($file);
    }

    public function testMissing() : void {
        $file = $this->file
            ->relative("Missing.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/cannot be found on the local filesystem/");

        new \pharos\phathom\Grammar($file);    
    }

    public function testReserved() : void {
        $file = $this->file
            ->relative("Reserved.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/lexer cannot be used as a rule name; ".
                ".*lexer.* ".
            "are reserved for directives, ".
            "got IDENT\(lexer\) at .*Reserved.grammar:0/");

        new \pharos\phathom\Grammar($file);
    }
}