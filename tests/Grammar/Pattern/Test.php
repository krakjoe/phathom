<?php
namespace pharos\phathom\tests\Grammar\Pattern;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testPattern() : void {
        $file = $this->file
            ->relative("Pattern.grammar");
        $content = $this->file
            ->relative("Pattern.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->assertSame($parser->parse(), [
            0 => [
                "one" => "42"
            ],
            1 => [
                "two" => "24"
            ],
            2 => [
                42 => 24
            ],
            3 => [
                "three" => [
                    "A", "B", "C"
                ]
            ]
        ]);
    }

    public function testAlphanumericDelimiter() : void {
        $file = $this->file
            ->relative("AlphanumericDelimiter.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/got alphanumeric/");
        
        new \pharos\phathom\Grammar($file);
    }

    public function testWhitespaceDelimiter() : void {
        $file = $this->file
            ->relative("WhitespaceDelimiter.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/got whitespace/");
        
        new \pharos\phathom\Grammar($file);
    }

    public function testBackslashDelimiter() : void {
        $file = $this->file
            ->relative("BackslashDelimiter.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/got backslash/");
        
        new \pharos\phathom\Grammar($file);
    }

    public function testImproperDelimiter() : void {
        $file = $this->file
            ->relative("ImproperDelimiter.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/improperly delimited/");
        
        new \pharos\phathom\Grammar($file);
    }

    public function testMalformedPattern() : void {
        $file = $this->file
            ->relative("MalformedPattern.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/compilation failed/i");
        
        new \pharos\phathom\Grammar($file);
    }

    public function testFlags() : void {
        $file = $this->file
            ->relative("Flags.grammar");
        $content = $this->file
            ->relative("Pattern.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->assertSame($parser->parse(), [
            0 => [
                "one" => "42"
            ],
            1 => [
                "two" => "24"
            ],
            2 => [
                42 => 24
            ],
            3 => [
                "three" => [
                    "A", "B", "C"
                ]
            ]
        ]);
    }

    public function testZeroMatch() : void {
        $file = $this->file
            ->relative("ZeroMatch.grammar");
        $content = $this->file
            ->relative("ZeroMatch.content");

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/pattern matches zero characters/");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);
        $parser->parse();
    }

    public function testTrailingSkip() : void {
        $file = $this->file
            ->relative("Pattern.grammar");
        $content = $this->file
            ->relative("TrailingSkip.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser  = new \pharos\phathom\Parser($grammar, $content);

        $this->assertSame($parser->parse(), [
            0 => [
                "one" => "42"
            ]
        ]);
    }
}