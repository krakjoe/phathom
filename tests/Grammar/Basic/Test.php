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
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->assertSame($parser->parse($content), [
            0 => [
                "one" => "42"
            ],
            1 => [
                "two" => "24"
            ]
        ]);

        $this->assertInstanceOf(
            \pharos\phathom\Context::class,
            $parser->context);

        $this->assertInstanceOf(
            \pharos\phathom\Grammar::class,
            $parser->context->grammar);
    }

    public function testBasicBufferInput() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $test = $this->file
            ->relative("Basic.content");

        $input = new \pharos\phathom\Buffer(
            "buffer", $test->contents);

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->assertSame($parser->parse($input), [
            0 => [
                "one" => "42"
            ],
            1 => [
                "two" => "24"
            ]
        ]);

        $this->assertInstanceOf(
            \pharos\phathom\Context::class,
            $parser->context);

        $this->assertInstanceOf(
            \pharos\phathom\Grammar::class,
            $parser->context->grammar);
    }

    public function testBasicNomatch() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $content = $this->file
            ->relative("BasicNomatch.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessageMatches(
            "/Unexpected character \";\"/");

        $parser->parse($content);
    }

    public function testBasicNoparse() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $content = $this->file
            ->relative("BasicNoparse.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessageMatches(
            "/Unexpected character/");

        $parser->parse($content);
    }

    public function testBasicNomatchEmpty() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $content = $this->file
            ->relative("BasicNomatchEmpty.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->expectException(
            \pharos\phathom\Exception\Execute::class);
        $this->expectExceptionMessageMatches(
            "/no input available/");

        $parser->parse($content);
    }

    public function testBasicIncomplete() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $content = $this->file
            ->relative("BasicIncomplete.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->expectException(
            \pharos\phathom\Exception\Execute::class);
        $this->expectExceptionMessageMatches(
            "/does not match 'unit'/");

        $parser->parse($content);
    }

    public function testBasicUnrecognizedDirective() : void {
        $file = $this->file
            ->relative("BasicUnrecognizedDirective.grammar");
        
        $this->expectException(
            \pharos\phathom\Exception\Directive::class);
        $this->expectExceptionMessageMatches(
            "/Unknown directive, expected ".
                ".* ".
            "got IDENT\(unrecognized\)/");

        new \pharos\phathom\Grammar($file);
    }

    public function testBasicUnrecognizedAnnotation() : void {
        $file = $this->file
            ->relative("BasicUnrecognizedAnnotation.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Annotation::class);
        $this->expectExceptionMessageMatches(
            "/Unknown annotation, ".
                ".* expected .* ".
            "got ANNOTATION\(unrecognized\)/");

        new \pharos\phathom\Grammar($file);
    }

    public function testBasicSerialization() : void {
        $file = $this->file
            ->relative("Basic.grammar");
        $content = $this->file
            ->relative("Basic.content");

        $object = new \pharos\phathom\Grammar($file);

        $grammar = \unserialize(
            \serialize($object));

        $parser  = new \pharos\phathom\Parser($grammar);
        $this->assertSame($parser->parse($content), [
            0 => [
                "one" => "42"
            ],
            1 => [
                "two" => "24"
            ]
        ]);

        $this->assertInstanceOf(
            \pharos\phathom\Context::class,
            $parser->context);

        $this->assertInstanceOf(
            \pharos\phathom\Grammar::class,
            $parser->context->grammar);
    }

    public function testBasicTrailing() : void {
        $file = $this->file
            ->relative("BasicTrailing.grammar");
        $content = $this->file
            ->relative("BasicTrailing.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar);

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessageMatches(
            "/Unexpected character \";\", expected end of input/");

        $parser->parse($content);
    }

    public function testBasicAssignment() : void {
        $file = $this->file
            ->relative("BasicAssignment.grammar");
        $grammar = new \pharos\phathom\Grammar($file);
        $this->assertTrue(
            \count($grammar->rules['complex']) === 2);
        $this->assertTrue(
            \count($grammar->rules['simple']) === 1);
        $this->assertTrue(
            \count($grammar->rules['multi']) === 2);
    }
}