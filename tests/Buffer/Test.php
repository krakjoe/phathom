<?php
namespace pharos\phathom\tests {
    use \pharos\phathom\Buffer;
    use \pharos\phathom\File;

    final class Test extends \PHPUnit\Framework\TestCase {
        public function testBuffer() : void {
            $source = "input string";
            $contents = "Hello World";

            $buffer = new Buffer($source, $contents);

            $this->assertSame($buffer->contents(), $contents);
            $this->assertSame((string) $buffer, $source);
            $this->assertSame($buffer->__debugInfo(), [
                'path' => $source,
            ]);
        }
    }
}
?>