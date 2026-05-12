<?php
namespace pharos\phathom\tests\Grammar\Quantifier\Optional;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testOptionalZero() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sOptional.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sOptionalZero.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser  = new \pharos\phathom\Parser($grammar, $file);
        $result  = $parser->parse();

        $this->assertSame($result->getThings(), []);
    }

    public function testOptionalOne() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sOptional.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sOptionalOne.content",
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
}