<?php
namespace pharos\phathom\tests\Grammar\Sequence;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testSequence() : void {
        $sequence = \sprintf(
            "%s%sSequence.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR,
        );
        $content = \sprintf(
            "%s%sSequence.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR,
        );

        $grammar = new \pharos\phathom\Grammar($sequence);
        $file    = new \pharos\phathom\File($content);
        $parser  = new \pharos\phathom\Parser($grammar, $file);

        $this->assertInstanceOf(
            \pharos\phathom\Node::class, $parser->parse());
    }
}