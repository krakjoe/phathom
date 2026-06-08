<?php
namespace pharos\phathom\tests\Grammar\Directive\Start;

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
            "/already declared/");

        new \pharos\phathom\Grammar($file);
    }

    public function testReserved() : void {
        $file = $this->file
            ->relative("Reserved.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/start cannot be used as a rule name; ".
                "token, context, lexer, include, and start ".
            "are reserved for directives, ".
            "got IDENT\(start\) at .*Reserved.grammar:0/");

        new \pharos\phathom\Grammar($file);
    }

    public function testUndefined() : void {
        $file = $this->file
            ->relative("Undefined.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Undefined::class);
        $this->expectExceptionMessageMatches(
            "/start rule 'undefined'/");

        new \pharos\phathom\Grammar($file);
    }
}