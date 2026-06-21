<?php
namespace pharos\phathom\tests\Assets {
    use \pharos\phathom\Exception\IO as IOException;
    use \pharos\phathom\Exception;
    
    class Test extends \PHPUnit\Framework\TestCase {
        private \pharos\phathom\Assets $assets;

        public function setUp() : void {
            $self = new \pharos\phathom\File(__FILE__);

            try {
                $default =
                    $self->relative(
                        "../../assets");
                $this->assets =
                    new \pharos\phathom\Assets($default);
            } catch(IOException $ex) {
                throw new Exception(
                    "could not find the default assets directory");
            }
        }

        public function testSerialization() : void {
            $object = \unserialize(
                \serialize($this->assets));
            $this->assertInstanceOf(
                \pharos\phathom\Assets::class, $object);
        }
    }
}