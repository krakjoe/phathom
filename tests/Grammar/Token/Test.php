<?php
namespace pharos\phathom\tests\Grammar\Token {

    final class Test extends \PHPUnit\Framework\TestCase {

        public function testString() : void {
            $this->assertSame(
                array_map(
                    \pharos\phathom\Grammar\Token::string(...),
                    [
                        \pharos\phathom\Grammar\Token::IDENT,
                        \pharos\phathom\Grammar\Token::PATTERN,
                        \pharos\phathom\Grammar\Token::STRING,
                        \pharos\phathom\Grammar\Token::QUANTIFIER,
                        \pharos\phathom\Grammar\Token::COLON,
                        \pharos\phathom\Grammar\Token::LIST_START,
                        \pharos\phathom\Grammar\Token::LIST_END,
                        \pharos\phathom\Grammar\Token::PRIORITY,
                        \pharos\phathom\Grammar\Token::ACTION,
                        \pharos\phathom\Grammar\Token::PIPE,
                        \pharos\phathom\Grammar\Token::END,
                        \pharos\phathom\Grammar\Token::EOF,
                        -1,
                    ]
                ), [
                    'IDENT',
                    'PATTERN',
                    'STRING',
                    'QUANTIFIER',
                    'COLON',
                    'LIST_START',
                    'LIST_END',
                    'PRIORITY',
                    'ACTION',
                    'PIPE',
                    'END',
                    'EOF',
                    'UNKNOWN',
                ]
            );
        }
    }
}
?>