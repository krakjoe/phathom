<?php
namespace pharos\phathom\tests\Grammar\Associativity;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testInconsistent() : void {
        $file = $this->file
            ->relative("Inconsistent.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Associativity::class);
        $this->expectExceptionMessageMatches(
            '/inconsistent for alternative 1/');

        new \pharos\phathom\Grammar($file);
    }

    public function testInert() : void {
        $file = $this->file
            ->relative("Inert.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Associativity::class);
        $this->expectExceptionMessageMatches('/inert/');

        new \pharos\phathom\Grammar($file);
    }

    public function testAmbiguous() : void {
        $file = $this->file
            ->relative("Ambiguous.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Associativity::class);
        $this->expectExceptionMessageMatches('/ambiguous.*missing/');

        new \pharos\phathom\Grammar($file);
    }

    public function testConflict() : void {
        $file = $this->file
            ->relative("Conflict.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Associativity::class);
        $this->expectExceptionMessageMatches('/ambiguous.*conflict/');

        new \pharos\phathom\Grammar($file);
    }

    public function testAssociativity() : void {
        $file = $this->file
            ->relative("Associativity.grammar");
        $content = $this->file
            ->relative("Associativity.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        // left: (one=two)=three
        $this->assertSame($parser->parse($content), [['one', 'two'], 'three']);
    }

    public function testRight() : void {
        $file = $this->file
            ->relative("Right.grammar");
        $content = $this->file
            ->relative("Associativity.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        // right: one=(two=three)
        $this->assertSame($parser->parse($content), ['one', ['two', 'three']]);
    }

    public function testRoot() : void {
        $file = $this->file
            ->relative("Root.grammar");
        $content = $this->file
            ->relative("Root.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        // Both alternatives match; left assoc with equal origin picks alt 0
        $this->assertSame('first', $parser->parse($content));
    }
}
