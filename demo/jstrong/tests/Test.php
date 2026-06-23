<?php
namespace pharos\phathom\demo\jstrong\tests {
    use pharos\phathom;

    class Test extends \PHPUnit\Framework\TestCase {
        protected phathom\File    $tests;
        protected phathom\Assets  $assets;
        protected phathom\Grammar $grammar;

        public function setUp() : void {
            $this->tests = new phathom\File(__FILE__);
            $this->assets = new phathom\Assets(
                $this->tests->relative(\sprintf(
                    "..%ssrc%sassets",
                    \DIRECTORY_SEPARATOR,
                    \DIRECTORY_SEPARATOR))
            );
            $this->grammar = new phathom\Grammar(
                $this->tests->relative(\sprintf(
                    "..%sgrammar/jstrong.grammar",
                    \DIRECTORY_SEPARATOR
                )),
                $this->assets
            );
        }

        protected function assertStrong(
            phathom\File $test, array $expected,
            string $assertion = "assertEquals") : void {
            $parser =
                new phathom\Parser($this->grammar);
            $this->$assertion(
                $parser->parse($test), $expected);
        }

        public function tearDown() : void {
            unset($this->assets);
        }
    }
}