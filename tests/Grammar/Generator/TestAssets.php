<?php
namespace pharos\phathom\tests\Grammar\Generator {
    class TestAssets extends \PHPUnit\Framework\TestCase {
        public function testExpectations() : void {
            $symbols = [];
            $generator =
                new \pharos\phathom\Grammar\Generator(
                    null, '\\pharos\\phathom\\Grammar\\Interface\\Engine', [
                        'token'   => '\\pharos\\phathom\\tests\\Grammar\\Generator\\Token',
                        'context' => '\\pharos\\phathom\\tests\\Grammar\\Generator\\Context'
                    ], 
                    new \pharos\phathom\Lexer(),
                    []);

            $symbols[] = $generator->generate();
            $symbols[] = $generator->generate();
            $this->assertSame(...$symbols);

            $this->assertTrue(\is_subclass_of($symbols[0][0], \pharos\phathom\Token::class));
            $this->assertTrue(\is_subclass_of($symbols[0][0], \pharos\phathom\tests\Grammar\Generator\Token::class));

            $this->assertTrue(\is_subclass_of($symbols[0][1], \pharos\phathom\Context::class));
            $this->assertTrue(\is_subclass_of($symbols[0][1], \pharos\phathom\tests\Grammar\Generator\Context::class));
        }
    }
}