<?php
namespace pharos\phathom\tests\Grammar;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testUnruled() : void {
        $unruled = \sprintf(
            "%s%sUnruled.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$unruled does not contain any rules");

        new \pharos\phathom\Grammar($unruled);
    }

    public function testUnlexed() : void {
        $unlexed = \sprintf(
            "%s%sUnlexed.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$unlexed does not declare a lexer");

        new \pharos\phathom\Grammar($unlexed);
    }

    public function testUnknownSymbol() : void {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(
            "/Unknown symbol 'undefined'/");

        new \pharos\phathom\Grammar(\sprintf(
            "%s%sUnknownSymbol.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
    }
}
