<?php
namespace pharos\phathom\tests\Grammar\Priority;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testInert() : void {
        $file = $this->file
            ->relative("Inert.grammar");
        
        $this->expectException(
            \pharos\phathom\Exception\Priority::class);
        $this->expectExceptionMessageMatches('/inert/');

        new \pharos\phathom\Grammar($file);
    }

    public function testInconsistentFirst() : void {
        $file = $this->file
            ->relative("InconsistentFirst.grammar");
        
        $this->expectException(
            \pharos\phathom\Exception\Priority::class);
        $this->expectExceptionMessageMatches(
            '/inconsistent for alternative 2/');

        new \pharos\phathom\Grammar($file);
    }

    public function testInconsistentSecond() : void {
        $file = $this->file
            ->relative("InconsistentSecond.grammar");
        
        $this->expectException(
            \pharos\phathom\Exception\Priority::class);
        $this->expectExceptionMessageMatches(
            '/inconsistent for alternative 2/');

        new \pharos\phathom\Grammar($file);
    }

    public function testPriority() : void {
        $file = $this->file
            ->relative("Priority.grammar");
        $content = $this->file
            ->relative("Priority.content");
        
        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar);
        
        $this->assertSame($parser->parse($content), [
            0 => [
                "high" => "one"
            ]
        ]);
    }

    public function testAmbiguous() : void {
        $file = $this->file
            ->relative("Ambiguous.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Priority::class);
        $this->expectExceptionMessageMatches('/ambiguous/');
            
        new \pharos\phathom\Grammar($file);
    }

    public function testRoot() : void {
        $file = $this->file
            ->relative("Root.grammar");
        $content = $this->file
            ->relative("Priority.content");
        
        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar);
        
        $this->assertSame($parser->parse($content), [
            "high" => "one"
        ]);
    }
}