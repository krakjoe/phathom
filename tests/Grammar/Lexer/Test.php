<?php
namespace pharos\phathom\tests\Grammar\Lexer;

use \pharos\phathom\Grammar\Token;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testComment() : void {
        $file = $this->file
            ->relative("Comment.grammar");
        $lexer = new \pharos\phathom\Grammar\Lexer($file);

        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::EOF, [
                'path'     => $file->path,
                'position' => 9
            ], null),
        ]);
    }

    public function testCommentWithNewLine() : void {
        $file = $this->file
            ->relative("CommentWithNewline.grammar");
        $lexer = new \pharos\phathom\Grammar\Lexer($file);

        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::EOF, [
                'path'     => $file->path,
                'position' => 10
            ], null),
        ]);
    }

    public function testListStartNotFollowingColonOrPipe() : void {
        $file = $this->file
            ->relative("ListStartNotFollowingColonOrPipe.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_START, ".
            "IDENT must be followed by ".
                "COLON, PIPE, QUANTIFIER, or END, ".
            "got LIST_START");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testListStartNotAllowedInList() : void {
        $file = $this->file
            ->relative("ListStartNotAllowedInList.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_START, ".
            "LIST_START must be followed by ".
                "IDENT or PATTERN, ".
            "got LIST_START");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testListStartNotTerminated() : void {
        $file = $this->file
            ->relative("ListStartNotTerminated.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected EOF, ".
            "IDENT must be followed by ".
                "IDENT, PATTERN, QUANTIFIER, or LIST_END, ".
            "got EOF");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testListEndEmpty() : void {
        $file = $this->file
            ->relative("ListEndEmpty.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_END, ".
            "LIST_START must be followed by ".
                "IDENT or PATTERN, ".
            "got LIST_END");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testList() : void {
        $file = $this->file
            ->relative("List.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,    ['path' => $file->path, 'position' => 0],  'ident'),
            new Token(Token::COLON,    ['path' => $file->path, 'position' => 5],  null),
            new Token(Token::LIST_START, ['path' => $file->path, 'position' => 7],  null),
            new Token(Token::IDENT,    ['path' => $file->path, 'position' => 8],  'ident'),
            new Token(Token::PATTERN,  ['path' => $file->path, 'position' => 14], 'pattern'),
            new Token(Token::LIST_END, ['path' => $file->path, 'position' => 23], null),
            new Token(Token::END,      ['path' => $file->path, 'position' => 24], null),
            new Token(Token::EOF,      ['path' => $file->path, 'position' => 25], null),
        ]);
    }

    public function testColonNotFollowingIdent() : void {
        $file = $this->file
            ->relative("ColonNotFollowingIdent.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected COLON, ".
            "COLON must be followed by ".
                "IDENT, STRING, LIST_START, or PATTERN, ".
            "got COLON");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testColonNotAllowedInList() : void {
        $file = $this->file
            ->relative("ColonNotAllowedInList.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected COLON, ".
            "IDENT must be followed by ".
                "IDENT, PATTERN, QUANTIFIER, or LIST_END, ".
            "got COLON");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testColonNotAllowedToDangle() : void {
        $file = $this->file
            ->relative("ColonNotAllowedToDangle.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected EOF, ".
            "COLON must be followed by ".
                "IDENT, STRING, LIST_START, or PATTERN, ".
            "got EOF");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testPipeNotAllowedInList() : void {
        $file = $this->file
            ->relative("PipeNotAllowedInList.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected PIPE, ".
            "IDENT must be followed by ".
                "IDENT, PATTERN, QUANTIFIER, or LIST_END, ".
            "got PIPE");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testPipeNotAllowedToDangle() : void {
        $file = $this->file
            ->relative("PipeNotAllowedToDangle.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected EOF, ".
            "PIPE must be followed by ".
                "IDENT, LIST_START, or PATTERN, ".
            "got EOF");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testPipe() : void {
        $file = $this->file
            ->relative("Pipe.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 0],  'ident'),
            new Token(Token::COLON,      ['path' => $file->path, 'position' => 5],  null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 8],  'ident'),
            new Token(Token::PIPE,       ['path' => $file->path, 'position' => 14], null),
            new Token(Token::PATTERN,    ['path' => $file->path, 'position' => 24], 'pattern'),
            new Token(Token::PIPE,       ['path' => $file->path, 'position' => 34], null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 44], 'ident'),
            new Token(Token::QUANTIFIER, ['path' => $file->path, 'position' => 49], '+'),
            new Token(Token::PIPE,       ['path' => $file->path, 'position' => 51], null),
            new Token(Token::LIST_START, ['path' => $file->path, 'position' => 60], null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 61], 'ident'),
            new Token(Token::LIST_END,   ['path' => $file->path, 'position' => 66], null),
            new Token(Token::ACTION,     ['path' => $file->path, 'position' => 68], ' action '),
            new Token(Token::END,        ['path' => $file->path, 'position' => 78], null),
            new Token(Token::EOF,        ['path' => $file->path, 'position' => 80], null),
        ]);
    }

    public function testPattern() : void {
        $file = $this->file
            ->relative("Pattern.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,   ['path' => $file->path, 'position' => 0],  'ident'),
            new Token(Token::COLON,   ['path' => $file->path, 'position' => 5],  null),
            new Token(Token::PATTERN, ['path' => $file->path, 'position' => 7],  'pattern'),
            new Token(Token::END,     ['path' => $file->path, 'position' => 16], null),
            new Token(Token::EOF,     ['path' => $file->path, 'position' => 17], null),
        ]);
    }

    public function testPatternEmpty() : void {
        $file = $this->file
            ->relative("PatternEmpty.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected empty PATTERN, ".
            "PATTERN must contain content between < and >");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testActionNotFollowingList() : void {
        $file = $this->file
            ->relative("ActionNotFollowingList.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected ACTION, ".
            "IDENT must be followed by ".
                "COLON, PIPE, QUANTIFIER, or END, ".
            "got ACTION");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testAction() : void {
        $file = $this->file
            ->relative("Action.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 0],  'ident'),
            new Token(Token::COLON,      ['path' => $file->path, 'position' => 5],  null),
            new Token(Token::LIST_START, ['path' => $file->path, 'position' => 7],  null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 8],  'ident'),
            new Token(Token::LIST_END,   ['path' => $file->path, 'position' => 13], null),
            new Token(Token::ACTION,     ['path' => $file->path, 'position' => 15], ' action '),
            new Token(Token::END,        ['path' => $file->path, 'position' => 25], null),
            new Token(Token::EOF,        ['path' => $file->path, 'position' => 26], null),
        ]);
    }

    public function testStringSingleQuoted() : void {
        $file = $this->file
            ->relative("StringSingleQuoted.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,  ['path' => $file->path, 'position' => 0],  'ident'),
            new Token(Token::COLON,  ['path' => $file->path, 'position' => 5],  null),
            new Token(Token::STRING, ['path' => $file->path, 'position' => 7],  "string'string"),
            new Token(Token::END,    ['path' => $file->path, 'position' => 23], null),
            new Token(Token::IDENT,  ['path' => $file->path, 'position' => 25], 'ident'),
            new Token(Token::COLON,  ['path' => $file->path, 'position' => 30], null),
            new Token(Token::STRING, ['path' => $file->path, 'position' => 32], '\n'),
            new Token(Token::END,    ['path' => $file->path, 'position' => 36], null),
            new Token(Token::EOF,    ['path' => $file->path, 'position' => 37], null),
        ]);
    }

    public function testStringDoubleQuoted() : void {
        $file = $this->file
            ->relative("StringDoubleQuoted.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,  ['path' => $file->path, 'position' => 0],  'ident'),
            new Token(Token::COLON,  ['path' => $file->path, 'position' => 5],  null),
            new Token(Token::STRING, ['path' => $file->path, 'position' => 7],  'string"string'),
            new Token(Token::END,    ['path' => $file->path, 'position' => 23], null),
            new Token(Token::IDENT,  ['path' => $file->path, 'position' => 25], 'ident'),
            new Token(Token::COLON,  ['path' => $file->path, 'position' => 30], null),
            new Token(Token::STRING, ['path' => $file->path, 'position' => 32], '\n'),
            new Token(Token::END,    ['path' => $file->path, 'position' => 36], null),
            new Token(Token::EOF,    ['path' => $file->path, 'position' => 37], null),
        ]);
    }

    public function testStringNotFollowingColon() : void {
        $file = $this->file
            ->relative("StringNotFollowingColon.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_START, ".
            "IDENT must be followed by ".
                "COLON, PIPE, QUANTIFIER, or END, ".
            "got LIST_START");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testStringNotTerminated() : void {
        $file = $this->file
            ->relative("StringNotTerminated.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected unterminated STRING, ".
            "STRING started with ' must be terminated by ', ".
            "got STRING(string;)");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testQuantifierNotFollowingQuantifiable() : void {
        $file = $this->file
            ->relative("QuantifiersNotFollowingQuantifiable.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected QUANTIFIER, ".
            "STRING must be followed by ".
                "COMMA or END, ".
            "got QUANTIFIER");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testQuantifiers() : void {
        $file = $this->file
            ->relative("Quantifiers.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);

        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 0],   'ident1'),
            new Token(Token::COLON,      ['path' => $file->path, 'position' => 6],   null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 8],   'ident1'),
            new Token(Token::QUANTIFIER, ['path' => $file->path, 'position' => 14],  '+'),
            new Token(Token::END,        ['path' => $file->path, 'position' => 16],  null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 18],  'ident2'),
            new Token(Token::COLON,      ['path' => $file->path, 'position' => 24],  null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 26],  'ident2'),
            new Token(Token::QUANTIFIER, ['path' => $file->path, 'position' => 32],  '*'),
            new Token(Token::END,        ['path' => $file->path, 'position' => 34],  null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 36],  'ident3'),
            new Token(Token::COLON,      ['path' => $file->path, 'position' => 42],  null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 44],  'ident3'),
            new Token(Token::QUANTIFIER, ['path' => $file->path, 'position' => 50],  '?'),
            new Token(Token::END,        ['path' => $file->path, 'position' => 52],  null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 55],  'pattern1'),
            new Token(Token::COLON,      ['path' => $file->path, 'position' => 63],  null),
            new Token(Token::PATTERN,    ['path' => $file->path, 'position' => 65],  'pattern1'),
            new Token(Token::QUANTIFIER, ['path' => $file->path, 'position' => 75],  '+'),
            new Token(Token::END,        ['path' => $file->path, 'position' => 77],  null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 79],  'pattern2'),
            new Token(Token::COLON,      ['path' => $file->path, 'position' => 87],  null),
            new Token(Token::PATTERN,    ['path' => $file->path, 'position' => 89],  'pattern2'),
            new Token(Token::QUANTIFIER, ['path' => $file->path, 'position' => 99],  '*'),
            new Token(Token::END,        ['path' => $file->path, 'position' => 101], null),
            new Token(Token::IDENT,      ['path' => $file->path, 'position' => 103], 'pattern3'),
            new Token(Token::COLON,      ['path' => $file->path, 'position' => 111], null),
            new Token(Token::PATTERN,    ['path' => $file->path, 'position' => 113], 'pattern3'),
            new Token(Token::QUANTIFIER, ['path' => $file->path, 'position' => 123], '?'),
            new Token(Token::END,        ['path' => $file->path, 'position' => 125], null),
            new Token(Token::EOF,        ['path' => $file->path, 'position' => 126], null),
        ]);
    }

    public function testUnexpectedCharacter() : void {
        $file = $this->file
            ->relative("UnexpectedCharacter.grammar");
        
        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected character \">\", expected IDENT");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testBalancedEscapeDelimiter() : void {
        $file = $this->file
            ->relative("BalancedEscapeDelimiter.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,   ['path' => $file->path, 'position' => 0],  'ident'),
            new Token(Token::COLON,   ['path' => $file->path, 'position' => 5],  null),
            new Token(Token::PATTERN, ['path' => $file->path, 'position' => 7],  'pattern<'),
            new Token(Token::END,     ['path' => $file->path, 'position' => 18], null),
            new Token(Token::EOF,     ['path' => $file->path, 'position' => 19], null),
        ]);
    }

    public function testBalancedEscapeLiteral() : void {
        $file = $this->file
            ->relative("BalancedEscapeLiteral.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertEquals($lexer->tokenize(), [
            new Token(Token::IDENT,   ['path' => $file->path, 'position' => 0],  'ident'),
            new Token(Token::COLON,   ['path' => $file->path, 'position' => 5],  null),
            new Token(Token::PATTERN, ['path' => $file->path, 'position' => 7],  'escape\\literal'),
            new Token(Token::END,     ['path' => $file->path, 'position' => 23], null),
            new Token(Token::IDENT,   ['path' => $file->path, 'position' => 26], 'ident'),
            new Token(Token::COLON,   ['path' => $file->path, 'position' => 31], null),
            new Token(Token::PATTERN, ['path' => $file->path, 'position' => 33], '\\'),
            new Token(Token::END,     ['path' => $file->path, 'position' => 37], null),
            new Token(Token::EOF,     ['path' => $file->path, 'position' => 38], null),
        ]);
    }

    public function testBalancedUnmatched() : void {
        $file = $this->file
            ->relative("BalancedUnmatched.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected unbalanced PATTERN, ".
            "PATTERN started with < and terminated by >, ".
                "may contain an unescaped <, or be missing >, ".
            "got PATTERN(<>;)");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testBalancedDanglingEscape() : void {
        $file = $this->file
            ->relative("BalancedDanglingEscape.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected escape in PATTERN, ".
            "PATTERN started with < and terminated by >, ".
                "must not end with an escape, ".
            "expected more input");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testAnnotationNotFollowingList() : void {
        $file = $this->file
            ->relative("AnnotationNotFollowingList.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected ANNOTATION, ".
            "COLON must be followed by ".
                "IDENT, STRING, LIST_START, or PATTERN, ".
            "got ANNOTATION(42)");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testAnnotationEmpty() : void {
        $file = $this->file
            ->relative("AnnotationEmpty.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected empty ANNOTATION, ".
            "ANNOTATION must contain content between ".
            "[ and ]");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testAnnotationNonAlphanum() : void {
        $file = $this->file
            ->relative("AnnotationNonAlphanum.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected annotation \"!\", ".
            "expected [0-9a-zA-Z]+");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testInitialNotIdent() : void {
        $file = $this->file
            ->relative("InitialNotIdent.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected ACTION, ".
            "initial token must be ".
                "IDENT, ".
            "got ACTION");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testPrintTruncation() : void {
        $file = $this->file
            ->relative("PrintTruncation.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected ACTION, ".
            "initial token must be ".
                "IDENT, ".
            "got ACTION(
        /* this must be more th...)");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testIdentInvalidInitialCharacter() : void {
        $file = $this->file
            ->relative("IdentInvalidInitialCharacter.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessageMatches(
            "/Unexpected character \"1\", expected IDENT ".
                "at .*IdentInvalidInitialCharacter.grammar:0/");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }
}