<?php
namespace pharos\phathom\tests\Grammar\Directive\Collector {
    use pharos\phathom\Grammar\Collector;

    final class Test extends \PHPUnit\Framework\TestCase {
        private \pharos\phathom\File $file;

        public function setUp() : void {
            $this->file = new \pharos\phathom\File(__FILE__);
        }

        public function testDuplicate() : void {
            $file =
                $this->file
                    ->relative("Duplicate.grammar");

            $this->expectException(\pharos\phathom\Exception\Directive::class);
            $this->expectExceptionMessageMatches(
                "/collector cannot be declared as \"defer\" ".
                    "at .*Duplicate.grammar:29, ".
                "collector already declared as \"off\" ".
                    "at .*Duplicate.grammar:11/");

            new \pharos\phathom\Grammar($file);
        }

        public function testInvalid() : void {
            $file = $this->file
                ->relative("Invalid.grammar");

            $this->expectException(
                \pharos\phathom\Exception\Directive::class);
            $this->expectExceptionMessageMatches(
                "/collector cannot be declared as \"invalid\", ".
                    "expected off, defer, or default ".
                "at .*Invalid.grammar:11/");

            new \pharos\phathom\Grammar($file);
        }

        public function testReserved() : void {
            $file = $this->file
                ->relative("Reserved.grammar");

            $this->expectException(
                \pharos\phathom\Exception\Directive::class);
            $this->expectExceptionMessageMatches(
                "/collector cannot be used as a rule name; ".
                    ".*start.* ".
                "are reserved for directives, ".
                "got IDENT\(collector\) at .*Reserved.grammar:0/");

            new \pharos\phathom\Grammar($file);
        }

        public function testDefault() : void {
            $file = $this->file
                ->relative("Default.grammar");
            $content = $this->file
                ->relative("../../Basic/Basic.content");

            $grammar = new \pharos\phathom\Grammar($file);
            $parser = new \pharos\phathom\Parser($grammar);
            $this->assertSame($parser->parse($content), [
                0 => [
                    "one" => "42"
                ],
                1 => [
                    "two" => "24"
                ]
            ]);
        }

        public function testOff() : void {
            $file = $this->file
                ->relative("Off.grammar");
            $content = $this->file
                ->relative("../../Basic/Basic.content");

            $grammar = new \pharos\phathom\Grammar($file);
            $parser = new \pharos\phathom\Parser($grammar);
            $this->assertSame($parser->parse($content), [
                0 => [
                    "one" => "42"
                ],
                1 => [
                    "two" => "24"
                ]
            ]);
        }

        public function testDefer() : void {
            $file = $this->file
                ->relative("Defer.grammar");
            $content = $this->file
                ->relative("../../Basic/Basic.content");

            $grammar = new \pharos\phathom\Grammar($file);
            $parser = new \pharos\phathom\Parser($grammar);
            $this->assertSame($parser->parse($content), [
                0 => [
                    "one" => "42"
                ],
                1 => [
                    "two" => "24"
                ]
            ]);
        }
    }
}
?>