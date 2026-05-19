<?php
namespace pharos\phathom\tests\Grammar\Priority;

final class Test extends \PHPUnit\Framework\TestCase
{
    public function testPriority() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sPriority.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file = new \pharos\phathom\File(\sprintf(
            "%s%sPriority.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser = new \pharos\phathom\Parser($grammar, $file);
        $result = $parser->parse();

        $this->assertSame($result->getThings(), [
            0 => [
                0 => "high",
                1 => "one"
            ]
        ]);
    }

    public function testPriorityPropagation() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sPriorityPropagation.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file = new \pharos\phathom\File(\sprintf(
            "%s%sPriorityPropagation.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser = new \pharos\phathom\Parser($grammar, $file);
        $result = $parser->parse();

        $this->assertSame($result->getThings(), [
            [
                0 => 2,
                1 => 'x',
            ]
        ]);
    }

    public function testRootSelection() : void {
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sRootSelection.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file = new \pharos\phathom\File(\sprintf(
            "%s%sPriorityPropagation.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser = new \pharos\phathom\Parser($grammar, $file);
        $result = $parser->parse();

        /* The higher-priority alternative (priority 2) must win. */
        $this->assertSame($result->getThings(), [
            [
                0 => 2,
                1 => 'x',
            ]
        ]);
    }

    public function testQuantifierPriority() : void {
        /* unit: (item+) [1] | (item+) [2] — the parent alt's declared
         * priority must floor the priority used in root selection even
         * though the synthetic __item_plus__ rule carries no priority of
         * its own. The [2] alternative must win. */
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sQuantifierPriority.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file = new \pharos\phathom\File(\sprintf(
            "%s%sPriorityPropagation.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser = new \pharos\phathom\Parser($grammar, $file);
        $result = $parser->parse();

        /* item's action fires first (adding [0, 'x']), then unit's action
         * fires with $1 = [null] (item+ collects returnThings' null returns).
         * The important assertion is that the [2] alternative wins, not [1]. */
        $this->assertSame($result->getThings(), [
            [0 => 0, 1 => 'x'],
            [0 => 2, 1 => [null]],
        ]);
    }

    public function testPrevPriority() : void {
        /* b: (a X) [1] | (a X) [4]  — when collectValues walks the token
         * back for the trailing X, back['prev'] is b@dot=1 which carries
         * the priority max(a_priority=3, b_alt_priority=4)=4.  That puts a
         * non-false priority through the 'prev' branch of selectPriority. */
        $grammar = new \pharos\phathom\Grammar(\sprintf(
            "%s%sPrevPriority.grammar",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));
        $file = new \pharos\phathom\File(\sprintf(
            "%s%sPrevPriority.content",
            \dirname(__FILE__),
            \DIRECTORY_SEPARATOR));

        $parser = new \pharos\phathom\Parser($grammar, $file);
        $result = $parser->parse();

        /* The [4] alternative of b must win. */
        $this->assertSame($result->getThings(), [
            [4, 'x'],
        ]);
    }
}