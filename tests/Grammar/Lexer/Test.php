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
                'type' => 'EOF',
                'location'     => [
                    'path'     => $comment,
                    'position' => 9
                ],
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
                'location'     => [
                    'path'     => $comment,
                    'position' => 10
                ],
            ],
        ]);
    }

    public function testListStartNotFollowingColonOrPipe() : void {
        $list = \sprintf(
            "%s%sListStartNotFollowingColonOrPipe.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected LIST_START, ".
            "IDENT must be followed by ".
                "COLON, PIPE, QUANTIFIER, or END, ".
            "got LIST_START");

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
            "LIST_START must be followed by ".
                "IDENT or PATTERN, ".
            "got LIST_START");

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
            "Unexpected EOF, ".
            "IDENT must be followed by ".
                "IDENT, PATTERN, QUANTIFIER, or LIST_END, ".
            "got EOF");

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
            "LIST_START must be followed by ".
                "IDENT or PATTERN, ".
            "got LIST_END");

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
                'location'     => [
                    'path'     => $list,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $list,
                    'position' => 5
                ],
            ],
            [
                'type'     => 'LIST_START',
                'location'     => [
                    'path'     => $list,
                    'position' => 7
                ],
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $list,
                    'position' => 8
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern',
                'location'     => [
                    'path'     => $list,
                    'position' => 14
                ],
            ],
            [
                'type'     => 'LIST_END',
                'location'     => [
                    'path'     => $list,
                    'position' => 23
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $list,
                    'position' => 24
                ],
            ],
            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $list,
                    'position' => 25
                ],
            ],
        ]);
    }

    public function testColonNotFollowingIdent() : void {
        $colon = \sprintf(
            "%s%sColonNotFollowingIdent.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected COLON, ".
            "COLON must be followed by ".
                "IDENT, STRING, LIST_START, or PATTERN, ".
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
            "IDENT must be followed by ".
                "IDENT, PATTERN, QUANTIFIER, or LIST_END, ".
            "got COLON");

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
                "IDENT, STRING, LIST_START, or PATTERN, ".
            "got EOF");

        $lexer = new \pharos\phathom\Grammar\Lexer($colon);
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
            "IDENT must be followed by ".
                "IDENT, PATTERN, QUANTIFIER, or LIST_END, ".
            "got PIPE");

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
                "IDENT, LIST_START, or PATTERN, ".
            "got EOF");

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
                'location'     => [
                    'path'     => $pipe,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 5
                ],
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 8
                ],
            ],
            [
                'type'     => 'PIPE',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 14
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 24
                ],
            ],
            [
                'type'     => 'PIPE',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 34
                ],
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 44
                ],
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '+',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 49
                ],
            ],
            [
                'type'     => 'PIPE',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 51
                ],
            ],
            [
                'type'     => 'LIST_START',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 60
                ],
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 61
                ],
            ],
            [
                'type'     => 'LIST_END',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 66
                ],
            ],
            [
                'type'     => 'ACTION',
                'value'    => 'action',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 68
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 78
                ],
            ],

            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $pipe,
                    'position' => 80
                ],
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
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $pattern,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $pattern,
                    'position' => 5
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern',
                'location'     => [
                    'path'     => $pattern,
                    'position' => 7
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $pattern,
                    'position' => 16
                ],
            ],

            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $pattern,
                    'position' => 17
                ],
            ],
        ]);
    }

    public function testActionNotFollowingList() : void {
        $action = \sprintf(
            "%s%sActionNotFollowingList.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected ACTION, ".
            "IDENT must be followed by ".
                "COLON, PIPE, QUANTIFIER, or END, ".
            "got ACTION");

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
                'location'     => [
                    'path'     => $action,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $action,
                    'position' => 5
                ],
            ],
            [
                'type'     => 'LIST_START',
                'location'     => [
                    'path'     => $action,
                    'position' => 7
                ],
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $action,
                    'position' => 8
                ],
            ],
            [
                'type'     => 'LIST_END',
                'location'     => [
                    'path'     => $action,
                    'position' => 13
                ],
            ],
            [
                'type'     => 'ACTION',
                'value'    => 'action',
                'location'     => [
                    'path'     => $action,
                    'position' => 15
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $action,
                    'position' => 25
                ],
            ],

            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $action,
                    'position' => 26
                ],
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
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $string,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $string,
                    'position' => 5
                ],
            ],
            [
                'type'     => 'STRING',
                'value'    => "string'string",
                'location'     => [
                    'path'     => $string,
                    'position' => 7
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $string,
                    'position' => 23
                ],
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $string,
                    'position' => 25
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $string,
                    'position' => 30
                ],
            ],
            [
                'type'     => 'STRING',
                'value'    => '\n',
                'location'     => [
                    'path'     => $string,
                    'position' => 32
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $string,
                    'position' => 36
                ],
            ],

            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $string,
                    'position' => 37
                ],
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
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $string,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $string,
                    'position' => 5
                ],
            ],
            [
                'type'     => 'STRING',
                'value'    => 'string"string',
                'location'     => [
                    'path'     => $string,
                    'position' => 7
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $string,
                    'position' => 23
                ],
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $string,
                    'position' => 25
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $string,
                    'position' => 30
                ],
            ],
            [
                'type'     => 'STRING',
                'value'    => '\n',
                'location'     => [
                    'path'     => $string,
                    'position' => 32
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $string,
                    'position' => 36
                ],
            ],

            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $string,
                    'position' => 37
                ],
            ],
        ]);
    }

    public function testStringNotFollowingColon() : void {
        $string = \sprintf(
            "%s%sStringNotFollowingColon.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected IDENT, ".
            "IDENT must be followed by ".
                "COLON, PIPE, QUANTIFIER, or END, ".
            "got IDENT");

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
            "Unexpected unterminated STRING, ".
            "STRING must be terminated by ', ".
            "got STRING(string;)");

        $lexer = new \pharos\phathom\Grammar\Lexer($string);
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
            "STRING must be followed by ".
                "END, ".
            "got QUANTIFIER");

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
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 6
                ],
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident1',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 8
                ],
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '+',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 14
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 16
                ],
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'ident2',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 18
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 24
                ],
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident2',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 26
                ],
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '*',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 32
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 34
                ],
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'ident3',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 36
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 42
                ],
            ],
            [
                'type'     => 'IDENT',
                'value'    => 'ident3',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 44
                ],
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '?',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 50
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 52
                ],
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'pattern1',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 55
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 63
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern1',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 65
                ],
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '+',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 75
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 77
                ],
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'pattern2',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 79
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 87
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern2',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 89
                ],
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '*',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 99
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 101
                ],
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'pattern3',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 103
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 111
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern3',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 113
                ],
            ],
            [
                'type'     => 'QUANTIFIER',
                'value'    => '?',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 123
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 125
                ],
            ],

            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $quantifiers,
                    'position' => 126
                ],
            ],
        ]);
    }

    public function testUnexpectedCharacter() : void {
        $unexpected = \sprintf(
            "%s%sUnexpectedCharacter.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected character \">\", expected IDENT");

        $lexer = new \pharos\phathom\Grammar\Lexer($unexpected);
        $lexer->tokenize();
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
                'location'     => [
                    'path'     => $balance,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $balance,
                    'position' => 5
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'pattern<',
                'location'     => [
                    'path'     => $balance,
                    'position' => 7
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $balance,
                    'position' => 18
                ],
            ],
            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $balance,
                    'position' => 19
                ],
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
                'location'     => [
                    'path'     => $balance,
                    'position' => 0
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $balance,
                    'position' => 5
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => 'escape\\literal',
                'location'     => [
                    'path'     => $balance,
                    'position' => 7
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $balance,
                    'position' => 23
                ],
            ],

            [
                'type'     => 'IDENT',
                'value'    => 'ident',
                'location'     => [
                    'path'     => $balance,
                    'position' => 26
                ],
            ],
            [
                'type'     => 'COLON',
                'location'     => [
                    'path'     => $balance,
                    'position' => 31
                ],
            ],
            [
                'type'     => 'PATTERN',
                'value'    => '\\',
                'location'     => [
                    'path'     => $balance,
                    'position' => 33
                ],
            ],
            [
                'type'     => 'END',
                'location'     => [
                    'path'     => $balance,
                    'position' => 37
                ],
            ],
            
            [
                'type'     => 'EOF',
                'location'     => [
                    'path'     => $balance,
                    'position' => 38
                ],
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
            "Unexpected unbalanced <> block, missing > in \"<>;\"");

        $lexer = new \pharos\phathom\Grammar\Lexer($balance);
        $lexer->tokenize();
    }

    public function testBalancedDanglingEscape() : void {
        $balance = \sprintf(
            "%s%sBalancedDanglingEscape.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected escape in <> block, expected more input");

        $lexer = new \pharos\phathom\Grammar\Lexer($balance);
        $lexer->tokenize();
    }

    public function testInitialNotIdent() : void {
        $initial = \sprintf(
            "%s%sInitialNotIdent.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected ACTION, ".
            "initial token must be ".
                "IDENT, ".
            "got ACTION");

        $lexer = new \pharos\phathom\Grammar\Lexer($initial);
        $lexer->tokenize();
    }

    public function testPrintTruncation() : void {
        $truncation = \sprintf(
            "%s%sPrintTruncation.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Unexpected ACTION, ".
            "initial token must be ".
                "IDENT, ".
            "got ACTION(/* this must be more than 32 cha...)");

        $lexer = new \pharos\phathom\Grammar\Lexer($truncation);
        $lexer->tokenize();
    }
}