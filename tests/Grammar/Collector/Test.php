<?php
namespace pharos\phathom\tests\Grammar\Collector {
    use pharos\phathom\Grammar\Collector;

    final class Test extends \PHPUnit\Framework\TestCase {
        public function testConstructor() : void {
            $this->assertSame(Collector::from('off'),      Collector::OFF);
            $this->assertSame(Collector::from('defer'),    Collector::DEFER);
            $this->assertSame(Collector::from('default'),  Collector::DEFAULT);
            $this->assertSame(Collector::from('nonsense'), Collector::UNKNOWN);
        }

        public function testOffWithGC() : void {
            \gc_enable();
            $restore =
                Collector::apply(
                    Collector::OFF);
            $this->assertFalse(
                \gc_enabled());
            Collector::restore($restore);
            $this->assertTrue(\gc_enabled());
        }

        public function testDeferWithGC() : void {
            \gc_enable();
            $restore =
                Collector::apply(
                    Collector::DEFER);
            $this->assertSame(
                Collector::COLLECT, $restore);
            $this->assertFalse(
                \gc_enabled());
            Collector::restore($restore);
            $this->assertTrue(\gc_enabled());
            /* note, we can't assert that
                \gc_collect_cycles() has been invoked */
        }

        public function testOffWithoutGC() : void {
            \gc_disable();
            $restore =
                Collector::apply(
                    Collector::OFF);
            $this->assertSame(
                Collector::DEFAULT, $restore);
            $this->assertFalse(
                \gc_enabled());
            Collector::restore($restore);
            $this->assertFalse(\gc_enabled());
        }

        public function testDeferWithoutGC() : void {
            \gc_disable();
            $restore =
                Collector::apply(
                    Collector::DEFER);
            $this->assertSame(
                Collector::DEFAULT, $restore);
            $this->assertFalse(
                \gc_enabled());
            Collector::restore($restore);
            $this->assertFalse(\gc_enabled());
        }

        public function testDefaultNoInterference() : void {
            $enabled = \gc_enabled();
            $restore =
                Collector::apply(
                    Collector::DEFAULT);
            $this->assertSame(
                Collector::DEFAULT, $restore);
            $this->assertSame($enabled, \gc_enabled());
            Collector::restore($restore);
            $this->assertSame($enabled, \gc_enabled());          
        }
    }
}
?>