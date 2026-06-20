<?php
namespace pharos\phathom\tests\Grammar\Directive\Optimizer;

final class Test extends \PHPUnit\Framework\TestCase {
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
            "/already added/");

        new \pharos\phathom\Grammar($file);
    }

    public function testNonexistent() : void {
        $file = $this->file
            ->relative("Nonexistent.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/cannot find .* must be autoloadable/");

        new \pharos\phathom\Grammar($file);
    }

    public function testInheritance() : void {
        $file = $this->file
            ->relative("Inheritance.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/must extend .*Optimization.* does not/");

        new \pharos\phathom\Grammar($file);
    }

    public function testBuiltin() : void {
        $file = $this->file
            ->relative("Builtin.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/optimizer .* ".
                "cannot be added at ".
            ".*Builtin.grammar:11, ".
                "already added \(builtin\)/");

        new \pharos\phathom\Grammar($file);
    }

    public function testThrows() : void {
        $file = $this->file
            ->relative("Throws.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Optimizer::class);
        $this->expectExceptionMessageMatches(
            "/while executing ".
                ".*Throws ".
            "\(from .*Throws.grammar:11\) ".
                "an uncaught exception \(Exception\) was thrown/");

        new \pharos\phathom\Grammar($file);
    }

    public function testOptimizer() : void {
        $file = $this->file
            ->relative("Optimizer.grammar");
        $content = $this->file
            ->relative("Optimizer.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar);
    
        $this->assertSame($parser->parse($content), [
            "hello"
        ]);
    }

    public function testLiterals() : void {
        $file = $this->file
            ->relative("Literals.grammar");
        $content = $this->file
            ->relative("Literals.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar);
    
        $this->assertSame(
            $parser->parse($content),
            $parser->parse($content));
    }
}