<?php
namespace pharos\phathom\tests\Grammar\Lexer;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testNonExistent() : void {
        $nonexistent = \sprintf(
            "%s%sNonexistent.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$nonexistent does not exist");

        new \pharos\phathom\Grammar\Lexer($nonexistent);
    }

    public function testComment() : void {
        $comment = \sprintf(
            "%s%sComment.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($comment);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'EOF',
                'position' => 9
            ],
        ]);
    }

    public function testCommentWithNewLine() : void {
        $comment = \sprintf(
            "%s%sCommentWithNewline.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($comment);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'EOF',
                'position' => 10,
            ],
        ]);
    }

    public function testListStartNotEnough() : void {
        $list = \sprintf(
            "%s%sListStartNotEnough.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_START, ".
            "LIST_START must follow COLON, ".
            "not enough tokens");

        $lexer = new \pharos\phathom\Grammar\Lexer($list);
        $lexer->tokenize();
    }

    public function testListStartNotFollowingColonOrPipe() : void {
        $list = \sprintf(
            "%s%sListStartNotFollowingColonOrPipe.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_START, ".
            "LIST_START must follow COLON or PIPE, ".
            "got IDENT");

        $lexer = new \pharos\phathom\Grammar\Lexer($list);
        $lexer->tokenize();
    }

    public function testListStartNotAllowedInList() : void {
        $list = \sprintf(
            "%s%sListStartNotAllowedInList.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_START, ".
            "EXPRESSION may only contain IDENT or PATTERN");

        $lexer = new \pharos\phathom\Grammar\Lexer($list);
        $lexer->tokenize();
    }

    public function testListStartNotTerminated() : void {
        $list = \sprintf(
            "%s%sListStartNotTerminated.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unterminated LIST_START, ".
            "expected )");

        $lexer = new \pharos\phathom\Grammar\Lexer($list);
        $lexer->tokenize();
    }

    public function testListEndNotEnough() : void {
        $list = \sprintf(
            "%s%sListEndNotEnough.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_END, ".
            "LIST_END must follow IDENT or PATTERN, ".
            "not enough tokens");

        $lexer = new \pharos\phathom\Grammar\Lexer($list);
        $lexer->tokenize();
    }

    public function testListEndEmpty() : void {
        $list = \sprintf(
            "%s%sListEndEmpty.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_END, ".
            "LIST_END must follow IDENT or PATTERN, ".
            "none listed");

        $lexer = new \pharos\phathom\Grammar\Lexer($list);
        $lexer->tokenize();
    }

    public function testList() : void {
        $list = \sprintf(
            "%s%sList.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($list);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 0,
            ],
            [
                'type'     => 'COLON',
                'position' => 5,
            ],
            [
                'type'     => 'LIST_START',
                'position' => 7,
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 8,
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern',
                'position' => 14,
            ],
            [
                'type'     => 'LIST_END',
                'position' => 23,
            ],
            [
                'type'     => 'EOF',
                'position' => 24
            ],
        ]);
    }

    public function testColonNotEnough() : void {
        $colon = \sprintf(
            "%s%sColonNotEnough.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected COLON, ".
            "COLON must follow IDENT, ".
            "not enough tokens");

        $lexer = new \pharos\phathom\Grammar\Lexer($colon);
        $lexer->tokenize();
    }

    public function testColonNotFollowingIdent() : void {
        $colon = \sprintf(
            "%s%sColonNotFollowingIdent.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected COLON, ".
            "COLON must follow IDENT, ".
            "got COLON");

        $lexer = new \pharos\phathom\Grammar\Lexer($colon);
        $lexer->tokenize();
    }

    public function testColonNotAllowedInList() : void {
        $colon = \sprintf(
            "%s%sColonNotAllowedInList.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected COLON, ".
            "EXPRESSION may only contain IDENT or PATTERN");

        $lexer = new \pharos\phathom\Grammar\Lexer($colon);
        $lexer->tokenize();
    }

    public function testColonNotAllowedToDangle() : void {
        $colon = \sprintf(
            "%s%sColonNotAllowedToDangle.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected EOF, ".
            "COLON must be followed by ".
                "IDENT, PATTERN, STRING, or LIST_START");

        $lexer = new \pharos\phathom\Grammar\Lexer($colon);
        $lexer->tokenize();
    }

    public function testPipeNotEnough() : void {
        $pipe = \sprintf(
            "%s%sPipeNotEnough.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected PIPE, ".
            "PIPE must follow IDENT, PATTERN, QUANTIFIER, LIST_END, or ACTION, ".
            "not enough tokens");

        $lexer = new \pharos\phathom\Grammar\Lexer($pipe);
        $lexer->tokenize();
    }

    public function testPipeNotFollowing() : void {
        $pipe = \sprintf(
            "%s%sPipeNotFollowing.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected PIPE, ".
            "PIPE must follow IDENT, PATTERN, QUANTIFIER, LIST_END, or ACTION, ".
            "got STRING");

        $lexer = new \pharos\phathom\Grammar\Lexer($pipe);
        $lexer->tokenize();
    }

    public function testPipeNotAllowedInList() : void {
        $pipe = \sprintf(
            "%s%sPipeNotAllowedInList.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected PIPE, ".
            "EXPRESSION may only contain IDENT or PATTERN");

        $lexer = new \pharos\phathom\Grammar\Lexer($pipe);
        $lexer->tokenize();
    }

    public function testPipeNotAllowedToDangle() : void {
        $pipe = \sprintf(
            "%s%sPipeNotAllowedToDangle.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected EOF, ".
            "PIPE must be followed by ".
                "IDENT, PATTERN, or LIST_START");

        $lexer = new \pharos\phathom\Grammar\Lexer($pipe);
        $lexer->tokenize();
    }

    public function testPipe() : void {
        $pipe = \sprintf(
            "%s%sPipe.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($pipe);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 0,
            ],
            [
                'type'     => 'COLON',
                'position' => 5,
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 8,
            ],
            [
                'type'     => 'PIPE',
                'position' => 14
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern',
                'position' => 24,
            ],
            [
                'type'     => 'PIPE',
                'position' => 34,
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 44,
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '+',
                'position' => 49,
            ],
            [
                'type'     => 'PIPE',
                'position' => 51,
            ],
            [
                'type'     => 'LIST_START',
                'position' => 60,
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 61,
            ],
            [
                'type'     => 'LIST_END',
                'position' => 66,
            ],
            [
                'type'     => 'ACTION',
                'value'    => 'action',
                'position' => 68
            ],
            [
                'type'     => 'EOF',
                'position' => 79,
            ],
        ]);
    }

    public function testPattern() : void {
        $pattern = \sprintf(
            "%s%sPattern.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($pattern);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern',
                'position' => 0,
            ],
            [
                'type'     => 'EOF',
                'position' => 9
            ],
        ]);
    }

    public function testActionNotEnough() : void {
        $action = \sprintf(
            "%s%sActionNotEnough.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected ACTION, ".
            "ACTION must follow LIST_END, ".
            "not enough tokens");

        $lexer = new \pharos\phathom\Grammar\Lexer($action);
        $lexer->tokenize();
    }

    public function testActionNotFollowingList() : void {
        $action = \sprintf(
            "%s%sActionNotFollowingList.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected ACTION, ".
            "ACTION must follow LIST_END, ".
            "got IDENT");

        $lexer = new \pharos\phathom\Grammar\Lexer($action);
        $lexer->tokenize();
    }

    public function testAction() : void {
        $action = \sprintf(
            "%s%sAction.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($action);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 0,
            ],
            [
                'type'     => 'COLON',
                'position' => 5,
            ],
            [
                'type'     => 'LIST_START',
                'position' => 7,
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 8,
            ],
            [
                'type'     => 'LIST_END',
                'position' => 13,
            ],
            [
                'type'     => 'ACTION',
                'value'    => 'action',
                'position' => 15,
            ],
            [
                'type'     => 'EOF',
                'position' => 25,
            ],
        ]);
    }

    public function testStringSingleQuoted() : void {
        $string = \sprintf(
            "%s%sStringSingleQuoted.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($string);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'DIRECTIVE',
                'value'    => 'ident',
                'position' => 0,
            ],
            [
                'type'     => 'COLON',
                'position' => 5,
            ],
            [
                'type'     => 'STRING',
                'value'    => "string'string",
                'position' => 7,
            ],

            [
                'type'     => 'DIRECTIVE',
                'value'    => 'ident',
                'position' => 24,
            ],
            [
                'type'     => 'COLON',
                'position' => 29,
            ],
            [
                'type'     => 'STRING',
                'value'    => '\n',
                'position' => 31,
            ],
            [
                'type'     => 'EOF',
                'position' => 35,
            ],
        ]);
    }

    public function testStringDoubleQuoted() : void {
        $string = \sprintf(
            "%s%sStringDoubleQuoted.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($string);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'DIRECTIVE',
                'value'    => 'ident',
                'position' => 0,
            ],
            [
                'type'     => 'COLON',
                'position' => 5,
            ],
            [
                'type'     => 'STRING',
                'value'    => 'string"string',
                'position' => 7,
            ],

            [
                'type'     => 'DIRECTIVE',
                'value'    => 'ident',
                'position' => 24,
            ],
            [
                'type'     => 'COLON',
                'position' => 29,
            ],
            [
                'type'     => 'STRING',
                'value'    => '\n',
                'position' => 31,
            ],
            [
                'type'     => 'EOF',
                'position' => 35
            ],
        ]);
    }

    public function testStringNotEnough() : void {
        $string = \sprintf(
            "%s%sStringNotEnough.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected STRING, ".
            "STRING must follow IDENT COLON, ".
            "not enough tokens");

        $lexer = new \pharos\phathom\Grammar\Lexer($string);
        $lexer->tokenize();
    }

    public function testStringNotFollowingColon() : void {
        $string = \sprintf(
            "%s%sStringNotFollowingColon.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected STRING, ".
            "STRING must follow IDENT COLON, ".
            "got IDENT IDENT");

        $lexer = new \pharos\phathom\Grammar\Lexer($string);
        $lexer->tokenize();
    }

    public function testStringNotTerminated() : void {
        $string = \sprintf(
            "%s%sStringNotTerminated.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unterminated STRING, expected '");

        $lexer = new \pharos\phathom\Grammar\Lexer($string);
        $lexer->tokenize();
    }

    public function testQuantifiersNotEnough() : void {
        $quantifiers = \sprintf(
            "%s%sQuantifiersNotEnough.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected QUANTIFIER, ".
            "QUANTIFIER must follow IDENT or PATTERN, ".
            "not enough tokens");

        $lexer = new \pharos\phathom\Grammar\Lexer($quantifiers);
        $lexer->tokenize();
    }

    public function testQuantifierNotFollowingQuantifiable() : void {
        $quantifiers = \sprintf(
            "%s%sQuantifiersNotFollowingQuantifiable.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected QUANTIFIER, ".
            "QUANTIFIER must follow IDENT or PATTERN, ".
            "got STRING");

        $lexer = new \pharos\phathom\Grammar\Lexer($quantifiers);
        $lexer->tokenize();
    }

    public function testQuantifiers() : void {
        $quantifiers = \sprintf(
            "%s%sQuantifiers.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($quantifiers);

        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'IDENT',
                'value'    => 'ident1',
                'position' => 0,
            ],
            [
                'type'     => 'COLON',
                'position' => 6,
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident1',
                'position' => 8,
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '+',
                'position' => 14,
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'ident2',
                'position' => 16,
            ],
            [
                'type'     => 'COLON',
                'position' => 22,
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident2',
                'position' => 24,
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '*',
                'position' => 30,
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'ident3',
                'position' => 32,
            ],
            [
                'type'     => 'COLON',
                'position' => 38,
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident3',
                'position' => 40,
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '?',
                'position' => 46,
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'pattern1',
                'position' => 49,
            ],
            [
                'type'     => 'COLON',
                'position' => 57,
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern1',
                'position' => 59,
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '+',
                'position' => 69,
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'pattern2',
                'position' => 71,
            ],
            [
                'type'     => 'COLON',
                'position' => 79,
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern2',
                'position' => 81,
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '*',
                'position' => 91,
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'pattern3',
                'position' => 93,
            ],
            [
                'type'     => 'COLON',
                'position' => 101,
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern3',
                'position' => 103,
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '?',
                'position' => 113,
            ],
            [
                'type'     => 'EOF',
                'position' => 114
            ],
        ]);
    }

    public function testUnexpected() : void {
        $unexpected = \sprintf(
            "%s%sUnexpected.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected >, expected IDENT");

        $lexer = new \pharos\phathom\Grammar\Lexer($unexpected);
        $lexer->tokenize();
    }

    public function testExhausted() : void {
        $exhausted = \sprintf(
            "%s%sExhausted.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($exhausted);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'IDENT',
                'value'    => 'exhaust',
                'position' => 0,
            ],
            [
                'type'     => 'EOF',
                'position' => 7
            ],
        ]);
    }

    public function testBalancedEscapeDelimiter() : void {
        $balance = \sprintf(
            "%s%sBalancedEscapeDelimiter.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($balance);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 0,
            ],
            [
                'type'     => 'COLON',
                'position' => 5,
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern<',
                'position' => 7,
            ],
            [
                'type'     => 'EOF',
                'position' => 18
            ],
        ]);
    }

    public function testBalancedEscapeLiteral() : void {
        $balance = \sprintf(
            "%s%sBalancedEscapeLiteral.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $lexer = new \pharos\phathom\Grammar\Lexer($balance);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 0,
            ],
            [
                'type'     => 'COLON',
                'position' => 5,
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'escape\\literal',
                'position' => 7,
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'position' => 25,
            ],
            [
                'type'     => 'COLON',
                'position' => 30,
            ],
            [
                'type'     => 'PATTERN',
                'value'    => '\\',
                'position' => 32,
            ],
            [
                'type'     => 'EOF',
                'position' => 36,
            ],
        ]);
    }

    public function testBalancedUnmatched() : void {
        $balance = \sprintf(
            "%s%sBalancedUnmatched.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unmatched < in \"<>\", missing >");

        $lexer = new \pharos\phathom\Grammar\Lexer($balance);
        $lexer->tokenize();
    }
}