<?php
namespace pharos\phathom\tests\Grammar\Basic;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testBasic() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sBasic.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file =  new \pharos\phathom\File(\sprintf(
            "%s%sBasic.content",
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

        $this->assertInstanceOf(
            \pharos\phathom\Grammar::class, $parser->getGrammar());
    }

    public function testBasicDefault() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sDefault.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $content = \sprintf(
            "%s%sBasic.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);
        $file =  new \pharos\phathom\File($content);

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

    public function testBasicNomatch() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sBasic.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $content = \sprintf(
            "%s%sBasicNomatch.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);
        $file =  new \pharos\phathom\File($content);

        $parser  = new \pharos\phathom\Parser($grammar, $file);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$content does not match Grammar: at 'unit'");

        $parser->parse();
    }
}