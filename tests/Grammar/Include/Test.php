<?php
namespace pharos\phathom\tests\Grammar\Include;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testInclude() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sInclude.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sInclude.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser  = new \pharos\phathom\Parser($grammar, $file);
        $result  = $parser->parse();
        $this->assertSame($result->getThings(), [
            0 => [
                0 => "one",
                1 => "42"
            ]
        ]);
    }

    public function testIncludeError() : void {
        $nonexistent = \sprintf(
            "%s%sNonexistent.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$nonexistent does not exist");

        new \pharos\phathom\Grammar(\sprintf(
            "%s%sError.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
    }
}