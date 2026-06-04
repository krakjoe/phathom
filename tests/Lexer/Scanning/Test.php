<?php
namespace pharos\phathom\tests\Lexer\Scanning;

final class Test extends \PHPUnit\Framework\TestCase {
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testSkipping() : void {
        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge(
            $this->file->relative("Skipping.lexer"));
        $lexer->compile();

        $position = 0;
        $content  = $this->file->relative("Skipping.content");

        while ($position < $content->length) {
            $token = $lexer->scan(
                $content, $position, [
                    1 => true,
                    2 => true,
                    3 => true,
                ], Token::class);
            if ($token === null) {
                break;
            }
            $tokens[] = $token;
        }

        $this->assertCount(4, $tokens);
        $this->assertContainsOnlyInstancesOf(Token::class, $tokens);

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

    public function testNoskip() : void {
        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge(
            $this->file->relative("Noskip.lexer"));
        $lexer->compile();

        $position = 0;
        $content  = $this->file
            ->relative("Noskip.content");
        $token = $lexer->scan(
            $content, $position, [1 => true], Token::class);

        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame("hello", $token->value);
        $this->assertSame($position, 5);
    }

    public function testEndOfInput() : void {
        $lexer = new \pharos\phathom\Lexer();
        $lexer->merge(
            $this->file->relative("Noskip.lexer"));
        $lexer->compile();

        $position = 0;
        $content  = $this->file->relative("Noskip.content");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessageMatches(
            "/Unexpected character \"h\", expected end of input/");

        $lexer->scan($content, $position, [], Token::class);
    }
}