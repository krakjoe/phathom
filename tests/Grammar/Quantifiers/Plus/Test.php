<?php
namespace pharos\phathom\tests\Grammar\Quantifiers\Plus;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testPlusOne() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sPlus.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sPlusOne.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser  = new \pharos\phathom\Parser($grammar, $file);
        $result  = $parser->parse();

        $this->assertSame($result->getThings(), [
            0 => [
                0 => "two",
                1 => "24"
            ]    
        ]);
    }

    public function testPlusMore() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sPlus.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sPlusMore.content",
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
            ]    
        ]);
    }
}