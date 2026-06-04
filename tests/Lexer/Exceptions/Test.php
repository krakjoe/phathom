<?php
namespace pharos\phathom\tests\Lexer\Exceptions;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testNoconfig() : void {
        $this->expectException(\pharos\phathom\Exception\Lexer::class);
        $this->expectExceptionMessageMatches(
            "/does not contain valid configuration \(ini syntax\)/");

        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge($this->file->relative("Noconfig.lexer"));
    }

    public function testRedefine() : void {
        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge($this->file->relative("Define.lexer"));
        
        $this->expectException(\pharos\phathom\Exception\Lexer::class);
        $this->expectExceptionMessageMatches(
            "/Token TOKEN with pattern .* ".
            "cannot be redefined in .*Redefine.lexer, ".
                "already defined as .* ".
            "in .*Define.lexer/");

        $lexer->merge($this->file->relative("Redefine.lexer"));
    }

    public function testIllegalBackslashDelimiter() : void {
        $lexer = new \pharos\phathom\Lexer();

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/Token TOKEN with pattern .* uses an illegal delimiter, ".
                "expected ".
                    "non-alphanumeric, ".
                    "non-whitespace, ".
                    "non-backslash, ".
                "got backslash ".
                "in .*IllegalBackslashDelimiter.lexer/");

        $lexer->merge($this->file->relative(
            "IllegalBackslashDelimiter.lexer"));
    }

    public function testIllegalAlphanumericDelimiter() : void {
        $lexer = new \pharos\phathom\Lexer();

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/Token TOKEN with pattern .* uses an illegal delimiter, ".
                "expected ".
                    "non-alphanumeric, ".
                    "non-whitespace, ".
                    "non-backslash, ".
                "got alphanumeric ".
                "in .*IllegalAlphanumericDelimiter.lexer/");

        $lexer->merge($this->file->relative(
            "IllegalAlphanumericDelimiter.lexer"));
    }

    public function testIllegalWhitespaceDelimiter() : void {
        $lexer = new \pharos\phathom\Lexer();

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/Token TOKEN with pattern .* uses an illegal delimiter, ".
                "expected ".
                    "non-alphanumeric, ".
                    "non-whitespace, ".
                    "non-backslash, ".
                "got whitespace ".
                "in .*IllegalWhitespaceDelimiter.lexer/");

        $lexer->merge($this->file->relative(
            "IllegalWhitespaceDelimiter.lexer"));
    }

    public function testImproperDelimiter() : void {
        $lexer = new \pharos\phathom\Lexer();

        $this->expectException(
            \pharos\phathom\Exception\Regex::class);
        $this->expectExceptionMessageMatches(
            "/Token TOKEN with pattern .* is improperly delimited, ".
                "starting delimiter \/ ".
                "expected ending delimiter \/ ".
            "in .*ImproperDelimiter.lexer/");

        $lexer->merge($this->file->relative(
            "ImproperDelimiter.lexer"));
    }

    public function testNopattern() : void {
        $this->expectException(\pharos\phathom\Exception\Lexer::class);
        $this->expectExceptionMessageMatches(
            "/Token definition for TOKEN is missing ".
                "a pattern in .*Nopattern.lexer/");

        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge($this->file->relative("Nopattern.lexer"));
    }

    public function testNocompile() : void {
        $this->expectException(\pharos\phathom\Exception\Lexer::class);
        $this->expectExceptionMessageMatches(
            "/Token TOKEN with pattern .* ".
                "failed to compile. ".
            "PCRE reported: .* in .*Nocompile.lexer/");

        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge($this->file->relative("Nocompile.lexer"));
    }

    public function testNocontent() : void {
        $this->expectException(\pharos\phathom\Exception\Lexer::class);
        $this->expectExceptionMessageMatches(
            "/Token TOKEN with pattern .* is erroneous, ".
            "matches zero characters in .*Nocontent.lexer/");

        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge($this->file->relative("Nocontent.lexer"));
    }
}