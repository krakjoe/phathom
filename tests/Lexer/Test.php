<?php
namespace pharos\phathom\tests\Lexer;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testConstructorNonExistent() : void {
        $nonexistent = \sprintf(
            "%s%sNonexistent.lexer",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$nonexistent does not exist");

        new \pharos\phathom\Lexer(__FILE__, "Nonexistent.lexer");
    }

    public function testConstructorMalformed() : void {
        $malformed = \sprintf(
            "%s%sMalformed.lexer",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$malformed does not contain valid configuration (ini syntax)");

        new \pharos\phathom\Lexer(__FILE__, "Malformed.lexer");
    }

    public function testConstructorContent() : void {
        $lexer = new \pharos\phathom\Lexer(__FILE__, "Content.lexer");

        $this->assertInstanceOf(\pharos\phathom\Lexer::class, $lexer);
    }

    public function testConstructorEmpty() : void {
        $empty = new \pharos\phathom\Lexer(__FILE__, "Empty.lexer");

        $this->assertInstanceOf(\pharos\phathom\Lexer::class, $empty);
    }

    public function testSkipping() : void {
        $content = new \pharos\phathom\Lexer(
            __FILE__, "Content.lexer");

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