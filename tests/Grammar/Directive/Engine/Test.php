<?php
namespace pharos\phathom\tests\Grammar\Directive\Engine {
    final class Test extends \PHPUnit\Framework\TestCase {
        private \pharos\phathom\File $file;

        public function setUp() : void {
            $this->file = new \pharos\phathom\File(__FILE__);
        }

        public function testDuplicate() : void {
            $this->expectException(
                \pharos\phathom\Exception\Directive::class);
            $this->expectExceptionMessageMatches(
                '/already declared/');

            new \pharos\phathom\Grammar(
                $this->file->relative(
                    "Duplicate.grammar"));
        }

        public function testNonexistent() : void {
            $this->expectException(
                \pharos\phathom\Exception\Directive::class);
            $this->expectExceptionMessageMatches(
                '/cannot find .* must be autoloadable/');

            new \pharos\phathom\Grammar(
                $this->file->relative(
                    "Nonexistent.grammar"));
        }

        public function testInterface() : void {
            $this->expectException(
                \pharos\phathom\Exception\Directive::class);
            $this->expectExceptionMessageMatches(
                '/must implement/');

            new \pharos\phathom\Grammar(
                $this->file->relative(
                    "Interface.grammar"));
        }
    }
}