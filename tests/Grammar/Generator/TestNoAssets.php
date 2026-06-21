<?php
namespace pharos\phathom\tests\Grammar\Generator {
    class TestNoAssets extends \PHPUnit\Framework\TestCase {
        private string $default;

        public function setUp() : void {
            $this->default = \sprintf(
                "%s%s..%s..%s..%sassets",
                \dirname(__FILE__),
                \DIRECTORY_SEPARATOR,
                \DIRECTORY_SEPARATOR,
                \DIRECTORY_SEPARATOR,
                \DIRECTORY_SEPARATOR);
            foreach (\scandir($this->default) as $asset) {
                if ($asset === "." || $asset === "..") {
                    continue;
                }

                \unlink(\sprintf(
                    "%s%s%s",
                    $this->default,
                    \DIRECTORY_SEPARATOR,
                    $asset));
            }

            \rmdir($this->default);
        }

        public function tearDown() : void {
            \mkdir($this->default);
            \touch(\sprintf(
                "%s%s.guard",
                $this->default,
                \DIRECTORY_SEPARATOR,
            ));
        }

        public function testExpectations() : void {
            $this->expectException(
                \pharos\phathom\Exception::class);
            $this->expectExceptionMessageMatches(
                "/could not find the default assets directory/");
            new \pharos\phathom\Grammar\Generator(
                null, '\\pharos\\phathom\\Grammar\\Interface\\Engine', [
                    'token'   => '\\pharos\\phathom\\tests\\Grammar\\Generator\\Token',
                    'context' => '\\pharos\\phathom\\tests\\Grammar\\Generator\\Context'
                ], 
                new \pharos\phathom\Lexer(),
                []);
        }
    }
}