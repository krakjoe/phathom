<?php
namespace pharos\phathom\tests\Grammar\Pattern\Exceptions;

final class Test extends \PHPUnit\Framework\TestCase {
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testIllegalBackslashDelimiter() : void {
        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/Token pattern .* uses an illegal delimiter, ".
                "expected ".
                    "non-alphanumeric, ".
                    "non-whitespace, ".
                    "non-backslash, ".
                "got backslash ".
                "in .*IllegalBackslashDelimiter.grammar/");

        new \pharos\phathom\Grammar(
            $this->file->relative(
                "IllegalBackslashDelimiter.grammar"));
    }

    public function testIllegalAlphanumericDelimiter() : void {
        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/Token pattern .* uses an illegal delimiter, ".
                "expected ".
                    "non-alphanumeric, ".
                    "non-whitespace, ".
                    "non-backslash, ".
                "got alphanumeric ".
                "in .*IllegalAlphanumericDelimiter.grammar/");

        new \pharos\phathom\Grammar(
            $this->file->relative(
                "IllegalAlphanumericDelimiter.grammar"));
    }

    public function testIllegalWhitespaceDelimiter() : void {
        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/Token pattern .* uses an illegal delimiter, ".
                "expected ".
                    "non-alphanumeric, ".
                    "non-whitespace, ".
                    "non-backslash, ".
                "got whitespace ".
                "in .*IllegalWhitespaceDelimiter.grammar/");

        new \pharos\phathom\Grammar(
            $this->file->relative(
                "IllegalWhitespaceDelimiter.grammar"));
    }

    public function testImproperDelimiter() : void {
        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/Token pattern .* is improperly delimited, ".
                "starting delimiter \/ ".
                "expected ending delimiter \/ ".
            "in .*ImproperDelimiter.grammar/");

        new \pharos\phathom\Grammar(
            $this->file->relative(
                "ImproperDelimiter.grammar"));
    }

    public function testNocompile() : void {
        $this->expectException(\pharos\phathom\Exception\Lexer::class);
        $this->expectExceptionMessageMatches(
            "/Token pattern .* ".
                "failed to compile. ".
            "PCRE reported: .* in .*Nocompile.grammar/");

        new \pharos\phathom\Grammar(
            $this->file->relative(
                "Nocompile.grammar"));
    }

    public function testNocontent() : void {
        $this->expectException(\pharos\phathom\Exception\Lexer::class);
        $this->expectExceptionMessageMatches(
            "/Token pattern .* is erroneous, ".
            "matches zero characters in .*Nocontent.grammar/");

        new \pharos\phathom\Grammar(
            $this->file->relative(
                "Nocontent.grammar"));
    }
}