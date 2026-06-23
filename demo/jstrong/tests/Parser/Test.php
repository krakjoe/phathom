<?php
namespace pharos\phathom\demo\jstrong\tests\Parser {
    use \pharos\phathom\demo\jstrong\tests\Test as TestCase;
    use \pharos\phathom;
    use \pharos\phathom\demo\jstrong;

    class Test extends TestCase {
        public function testSmoke() : void {
            $case =
                $this->tests->relative(
                    "Parser/smoke.jstrong");
            /* json.grammar */
            $this->assertStrong($case, [
                "string" => "string",
                "integer" => 42,
                "float" => 3.13,
                "array" => [1,2,3],
                "empty" => [],
                "trailing" => [
                    "content" => "comma",
                ],
                "entry" => [
                    0 => "c",
                    1 => "b",
                    2 => "d",
                ],
                "uninitialized" => []
            ]);
        }

        public function testTyped() : void {
            $case = $this->tests->relative(
                "Parser/typed.jstrong");

            $this->assertStrong($case, [
                "object" => new jstrong\tests\Config([ /* __action_typed_0__ */
                    "property" => new jstrong\tests\Config([ /* __action_typed_1__ */
                        "property" => "value",
                    ])
                ])
            ]);
        }

        public function testReference() : void {
            $case = $this->tests->relative(
                "Parser/reference.jstrong");

            $property = new jstrong\tests\Config([ /* __action_typed_1__ */
                "property" => "value",
            ]);

            $object = new jstrong\tests\Config([ /* __action_typed_0__ */
                "property" => $property
            ]);

            $this->assertStrong($case, [
                "indexed"   => [42, ["pi" => 3.14]],
                "object"    => $object,
                "reference" => $object, 
                    /* __action_reference_0__ ->
                            __action_chain_0__ */
                "reference-property"        =>  $property, /* __action_chain_1__ */
                "reference-property-nested" => "value",
                "reference-index"           =>  42,        /* __action_chain_2__ */
                "reference-index-nested"    =>  3.14       /* __action_chain_3__ */
            ]);
        }

        public function testTokens() : void {
            $class = new \ReflectionClass($this->grammar->token);
            foreach ($class->getReflectionConstants() as $constant) {
                $this->assertSame(
                    $constant->name,
                    $this->grammar->token::string(
                        $constant->getValue()));
            }
            $this->assertSame('UNKNOWN',
                $this->grammar->token::string(42));
        }
    }
}