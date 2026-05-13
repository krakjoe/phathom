<?php
namespace pharos\phathom\tests\Grammar;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testNonExistent() : void {
        $nonexistent = \sprintf(
            "%s%sNonexistent.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$nonexistent does not exist");

        new \pharos\phathom\Grammar($nonexistent);
    }

    public function testEmpty() : void {
        $empty = \sprintf(
            "%s%sEmpty.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$empty does not contain valid grammar (empty)");

        new \pharos\phathom\Grammar($empty);
    }

    public function testEOF() : void {
        $empty = \sprintf(
            "%s%sEOF.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected EOF in rule 'unit'");

        new \pharos\phathom\Grammar($empty);
    }

    public function testExpect() : void {
        $empty = \sprintf(
            "%s%sExpect.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Expected COLON, got IDENT(ALPHA) near token 4");

        new \pharos\phathom\Grammar($empty);
    }

    public function testUnexpected() : void {
        $empty = \sprintf(
            "%s%sUnexpected.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected COLON in rule 'unit'");

        new \pharos\phathom\Grammar($empty);
    }

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
}