<?php
namespace pharos\phathom\tests\Lexer;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testConstructorMalformed() : void {
        $this->expectException(\pharos\phathom\Exception\IO::class);
        $this->expectExceptionMessageMatches(
            "/does not contain valid configuration \(ini syntax\)/");

        new \pharos\phathom\Lexer(
            $this->file->relative("Malformed.lexer"));
    }

    public function testConstructorContent() : void {
        $lexer = new \pharos\phathom\Lexer(
            $this->file->relative("Content.lexer"));

        $this->assertInstanceOf(\pharos\phathom\Lexer::class, $lexer);
    }

    public function testConstructorEmpty() : void {
        $empty = new \pharos\phathom\Lexer(
            $this->file->relative("Empty.lexer"));

        $this->assertInstanceOf(\pharos\phathom\Lexer::class, $empty);
    }

    public function testSkipping() : void {
        $content = new \pharos\phathom\Lexer(
            $this->file->relative("Content.lexer"));

        $path = \sprintf(
            "%s%sSkipping.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR
        );

        $file = new \pharos\phathom\File($path);

        $this->assertSame($content->tokenize($file), [
            [
                "type" => "ALPHANUM",
                "value" => "Hello",
                "location" => [
                    "path"     => $path,
                    "position" => 0,
                ],
            ],
            [
                "type" => "ALPHANUM",
                "value" => "World",
                "location" => [
                    "path"     => $path,
                    "position" => 7,
                ],
            ],
            [
                "type" => "ALPHANUM",
                "value" => "Indented",
                "location" => [
                    "path"     => $path,
                    "position" => 18,
                ],
            ],
            [
                "type" => "INTEGER",
                "value" => "42",
                "location" => [
                    "path"     => $path,
                    "position" => 32,
                ],
            ]
        ]);
    }
}