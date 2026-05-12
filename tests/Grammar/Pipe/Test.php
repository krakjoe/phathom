<?php
namespace pharos\phathom\tests\Grammar\Pipe;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testPipe() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sPipe.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sPipe.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser  = new \pharos\phathom\Parser($grammar, $file);
        $result  = $parser->parse();
        $this->assertSame($result->getThings(), [
            "section" => [
                "item" => "value"
            ]
        ]);
    }
}