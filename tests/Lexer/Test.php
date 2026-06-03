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

        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge($this->file->relative("Malformed.lexer"));
    }

    public function testConstructorContent() : void {
        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge($this->file->relative("Content.lexer"));

        $this->assertInstanceOf(\pharos\phathom\Lexer::class, $lexer);
    }

    public function testConstructorEmpty() : void {
        $empty = new \pharos\phathom\Lexer();
        $empty->merge($this->file->relative("Empty.lexer"));

        $this->assertInstanceOf(\pharos\phathom\Lexer::class, $empty);
    }

    public function testSkipping() : void {
        $content = new \pharos\phathom\Lexer();
        $content->merge($this->file->relative("Content.lexer"));
        $content->compile();

        $path = \sprintf(
            "%s%sSkipping.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR
        );

        $file = new \pharos\phathom\File($path);

        $tokens = $content->tokenize($file, Token::class);

        $this->assertCount(4, $tokens);
        $this->assertContainsOnlyInstancesOf(\pharos\phathom\Token::class, $tokens);

        $this->assertSame("Hello",    $tokens[0]->value);
        $this->assertSame("World",    $tokens[1]->value);
        $this->assertSame("Indented", $tokens[2]->value);
        $this->assertSame("42",       $tokens[3]->value);

        $this->assertSame($tokens[0]->type, $tokens[1]->type); /* ALPHANUM */
        $this->assertSame($tokens[0]->type, $tokens[2]->type); /* ALPHANUM */
        $this->assertNotSame($tokens[0]->type, $tokens[3]->type); /* INTEGER != ALPHANUM */

        $this->assertSame(0,  $tokens[0]->location['position']);
        $this->assertSame(7,  $tokens[1]->location['position']);
        $this->assertSame(18, $tokens[2]->location['position']);
        $this->assertSame(28, $tokens[3]->location['position']);
    }
}