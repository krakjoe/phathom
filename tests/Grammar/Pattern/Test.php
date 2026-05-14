<?php
namespace pharos\phathom\tests\Grammar\Pattern;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testPattern() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sPattern.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sPattern.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser  = new \pharos\phathom\Parser($grammar, $file);
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