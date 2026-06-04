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