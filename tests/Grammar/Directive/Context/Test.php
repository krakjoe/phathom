<?php
namespace pharos\phathom\tests\Grammar\Directive\Context;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testNonexistent() : void {
        $file = $this->file
            ->relative("Nonexistent.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            '/cannot find .* must be autoloadable/');

        new \pharos\phathom\Grammar($file);
    }

    public function testInheritance() : void {
        $file = $this->file
            ->relative("Inheritance.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/must extend .* does not/");

        new \pharos\phathom\Grammar($file);
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
            "/context cannot be used as a rule name; ".
                "token, context, lexer, include, and start ".
            "are reserved for directives, ".
            "got IDENT\(context\) at .*Reserved.grammar:0/");

        new \pharos\phathom\Grammar($file);
    }
}
