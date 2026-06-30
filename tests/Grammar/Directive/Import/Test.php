<?php
namespace pharos\phathom\tests\Grammar\Directive\Import {
    final class Test extends \PHPUnit\Framework\TestCase {
        private \pharos\phathom\File $file;

        public function setUp() : void {
            $this->file = new \pharos\phathom\File(__FILE__);
        }

        public function testNonexistent() : void {
            $file = $this->file->relative(
                "Nonexistent.grammar");

            $this->expectException(
                \pharos\phathom\Exception\Directive::class);
            $this->expectExceptionMessageMatches(
                '/cannot find '.
                    '.*Nonexistent for import, '.
                'it must be autoloadable at .*Nonexistent.grammar:8/');

            new \pharos\phathom\Grammar($file);
        }

        public function testImport() : void {
            $file =
                $this->file->relative('Import.grammar');
            $grammar = new \pharos\phathom\Grammar($file);
            $parser = new \pharos\phathom\Parser($grammar);
            $result = $parser->parse(
                $this->file->relative('Import.content'));
            $this->assertSame($result,
                'pharos\phathom\tests\Grammar\Directive\Import\Symbol');
        }
    }
}