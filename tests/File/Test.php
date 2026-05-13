<?php
namespace pharos\phathom\tests\File;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testConstructorSuccess(): void
    {
        $file = new \pharos\phathom\File(__FILE__);
        $this->assertInstanceOf(
            \pharos\phathom\File::class, $file);
    }

    public function testConstructorFailure() : void {
        $nonexistent = \sprintf(
            "%s%snonexistent.txt",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "$nonexistent does not exist");

        new \pharos\phathom\File($nonexistent);
    }

    public function testBuffering() : void {
        $file = new \pharos\phathom\File(__FILE__);

        $this->assertFalse($file->buffered());

        $file->buffer();

        $this->assertTrue($file->buffered());
        $this->assertNotEmpty($file->getBuffer());
    }

    public function testFetching() : void {
        $file = new \pharos\phathom\File(__FILE__);
        $this->assertFalse($file->buffered());
        $this->assertNotEmpty($file->getBuffer());
        $this->assertTrue($file->buffered());
    }

    public function testCaching() : void {
        $file = new \pharos\phathom\File(__FILE__);

        $file->buffer();
        $first  = $file->getBuffer();
        $file->buffer();
        $second = $file->getBuffer();

        $this->assertSame($first, $second);
    }

    public function testEmptiness() : void {
        $temporary = \tmpfile();
        $meta      = \stream_get_meta_data($temporary);
        $file = new \pharos\phathom\File($meta['uri']);

        $buffer = $file->getBuffer();
        $this->assertEmpty($buffer);
        $this->assertTrue($buffer !== false);
    }

    public function testPath() : void {
        $file = new \pharos\phathom\File(__FILE__);

        $this->assertEquals(__FILE__, $file->getPath());
    }
}