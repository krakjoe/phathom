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
        $result  = $parser->parse();

        $this->assertSame($result->getThings(), [
            0 => [
                0 => "one",
                1 => "42"
            ],
            1 => [
                0 => "two",
                1 => "24"
            ],
            2 => [
                0 => 42,
                1 => 24
            ],
            3 => [
                0 => "three",
                1 => [
                    "A", "B", "C"
                ]
            ]
        ]);
    }
}