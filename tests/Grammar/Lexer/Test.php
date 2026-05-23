<?php
namespace pharos\phathom\tests\Grammar\Lexer;

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

        $this->assertSame($lexer->tokenize(), [
            [
                'type' => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 9
                ],
            ],
        ]);
    }

    public function testCommentWithNewLine() : void {
        $file = $this->file
            ->relative("CommentWithNewline.grammar");
        $lexer = new \pharos\phathom\Grammar\Lexer($file);

        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 10
                ],
            ],
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
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 5
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::LIST_START,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 7
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 8
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => 'pattern',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 14
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::LIST_END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 23
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 24
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 25
                ],
            ],
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
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 5
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 8
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PIPE,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 14
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => 'pattern',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 24
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PIPE,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 34
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 44
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::QUANTIFIER,
                'value'    => '+',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 49
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PIPE,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 51
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::LIST_START,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 60
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 61
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::LIST_END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 66
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::ACTION,
                'value'    => ' action ',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 68
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 78
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 80
                ],
            ],
        ]);
    }

    public function testPattern() : void {
        $file = $this->file
            ->relative("Pattern.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 5
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => 'pattern',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 7
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 16
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 17
                ],
            ],
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
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 5
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::LIST_START,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 7
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 8
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::LIST_END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 13
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::ACTION,
                'value'    => ' action ',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 15
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 25
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 26
                ],
            ],
        ]);
    }

    public function testStringSingleQuoted() : void {
        $file = $this->file
            ->relative("StringSingleQuoted.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 5
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::STRING,
                'value'    => "string'string",
                'location'     => [
                    'path'     => $file->path,
                    'position' => 7
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 23
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 25
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 30
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::STRING,
                'value'    => '\n',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 32
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 36
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 37
                ],
            ],
        ]);
    }

    public function testStringDoubleQuoted() : void {
        $file = $this->file
            ->relative("StringDoubleQuoted.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 5
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::STRING,
                'value'    => 'string"string',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 7
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 23
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 25
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 30
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::STRING,
                'value'    => '\n',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 32
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 36
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 37
                ],
            ],
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
                "END, ".
            "got QUANTIFIER");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testQuantifiers() : void {
        $file = $this->file
            ->relative("Quantifiers.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);

        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident1',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 6
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident1',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 8
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::QUANTIFIER,
                'value'    => '+',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 14
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 16
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident2',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 18
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 24
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident2',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 26
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::QUANTIFIER,
                'value'    => '*',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 32
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 34
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident3',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 36
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 42
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident3',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 44
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::QUANTIFIER,
                'value'    => '?',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 50
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 52
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'pattern1',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 55
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 63
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => 'pattern1',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 65
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::QUANTIFIER,
                'value'    => '+',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 75
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 77
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'pattern2',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 79
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 87
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => 'pattern2',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 89
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::QUANTIFIER,
                'value'    => '*',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 99
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 101
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'pattern3',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 103
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 111
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => 'pattern3',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 113
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::QUANTIFIER,
                'value'    => '?',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 123
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 125
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 126
                ],
            ],
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
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 5
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => 'pattern<',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 7
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 18
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 19
                ],
            ],
        ]);
    }

    public function testBalancedEscapeLiteral() : void {
        $file = $this->file
            ->relative("BalancedEscapeLiteral.grammar");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $this->assertSame($lexer->tokenize(), [
            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 0
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 5
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => 'escape\\literal',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 7
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 23
                ],
            ],

            [
                'type'     => \pharos\phathom\Grammar\Token::IDENT,
                'value'    => 'ident',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 26
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::COLON,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 31
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::PATTERN,
                'value'    => '\\',
                'location'     => [
                    'path'     => $file->path,
                    'position' => 33
                ],
            ],
            [
                'type'     => \pharos\phathom\Grammar\Token::END,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 37
                ],
            ],
            
            [
                'type'     => \pharos\phathom\Grammar\Token::EOF,
                'location'     => [
                    'path'     => $file->path,
                    'position' => 38
                ],
            ],
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

    public function testPriorityNotFollowingList() : void {
        $file = $this->file
            ->relative("PriorityNotFollowingList.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected PRIORITY, ".
            "COLON must be followed by ".
                "IDENT, STRING, LIST_START, or PATTERN, ".
            "got PRIORITY(42)");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testPriorityEmpty() : void {
        $file = $this->file
            ->relative("PriorityEmpty.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected empty PRIORITY, ".
            "PRIORITY must contain content between ".
            "[ and ]");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testPriorityNonDigit() : void {
        $file = $this->file
            ->relative("PriorityNonDigit.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected non-digit in PRIORITY, ".
            "PRIORITY may only contain digits, ".
            "got !");

        $lexer = new \pharos\phathom\Grammar\Lexer($file);
        $lexer->tokenize();
    }

    public function testPriorityUnterminated() : void {
        $file = $this->file
            ->relative("PriorityUnterminated.grammar");

        $this->expectException(
            \pharos\phathom\Exception\Unexpected::class);
        $this->expectExceptionMessage(
            "Unexpected unterminated PRIORITY, ".
            "PRIORITY started with [ must be terminated by ]");

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
}