<?php
namespace pharos\phathom\tests\Grammar\Quantifiers\Asterix;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testAsterixZero() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sAsterix.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sAsterixZero.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser  = new \pharos\phathom\Parser($grammar, $file);
        $result  = $parser->parse();

        $this->assertSame($result->getThings(), []);
    }

    public function testAsterixOne() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sAsterix.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sAsterixOne.content",
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

    public function testAsterixMore() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sAsterix.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sAsterixMore.content",
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