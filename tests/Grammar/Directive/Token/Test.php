<?php
namespace pharos\phathom\tests\Grammar\Directive\Token;

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
            "/does not exist/");

        new \pharos\phathom\Grammar($file);
    }

    public function testInheritance() : void {
        $file = $this->file
            ->relative("Inheritance.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/does not extend/");

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

    public function testToken() : void {
        $file = $this->file
            ->relative("Token.grammar");
        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar);
        $this->assertInstanceOf(
            \pharos\phathom\tests\Grammar\Directive\Token\Token::class,
            $parser->parse(
                new \pharos\phathom\Buffer(
                    "test input", "hello world")
            ));
    }
}
