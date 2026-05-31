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

        $this->expectException(\pharos\phathom\Exception\IO::class);
        $this->expectExceptionMessageMatches(
            "/cannot be found on the local filesystem/");

        new \pharos\phathom\File($nonexistent);
    }

    public function testCaching() : void {
        $file = new \pharos\phathom\File(__FILE__);

        $this->assertSame(
            $file->contents(),
            $file->contents());
    }

    public function testEmptiness() : void {
        $temporary = \tmpfile();
        $meta      = \stream_get_meta_data($temporary);
        $file = new \pharos\phathom\File($meta['uri']);

        $buffer =
            $file->contents();
        $this->assertEmpty($buffer);
        $this->assertTrue($buffer !== false);
    }

    public function testPath() : void {
        $file = new \pharos\phathom\File(__FILE__);

        $this->assertEquals(__FILE__, $file->path);
    }

    public function testPutOnNonDirectory() : void {
        $file = new \pharos\phathom\File(__FILE__);

        $this->expectException(\pharos\phathom\Exception\IO::class);
        $this->expectExceptionMessageMatches(
            "/is not a directory/");
        
        $file->put("relative", "contents");
    }

    public function testSerialization() : void {
        $file = new \pharos\phathom\File(__FILE__);
        $file->contents();

        $object = \unserialize(
            \serialize($file));

        $this->assertSame($object->kind,
            \pharos\phathom\FILE::REGULAR);
    }

    public function testDebug() : void {
        $file = new \pharos\phathom\File(__FILE__);
        $file->contents();

        $this->assertSame($file->__debugInfo(), [
            'path' => __FILE__,
            'kind' => \pharos\phathom\FILE::REGULAR,
        ]);
    }
}