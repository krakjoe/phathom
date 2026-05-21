<?php
namespace pharos\phathom\tests\Grammar\Priority;

final class Test extends \PHPUnit\Framework\TestCase
{
    private \pharos\phathom\File $file;

    public function setUp() : void {
        $this->file = new \pharos\phathom\File(__FILE__);
    }

    public function testPriority() : void {
        $file = $this->file
            ->relative("Priority.grammar");
        $content = $this->file
            ->relative("Priority.content");
        
        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar, $content);
        $result = $parser->parse();

        $this->assertSame($result->getThings(), [
            0 => [
                0 => "high",
                1 => "one"
            ]
        ]);
    }

    public function testPriorityPropagation() : void {
        $file = $this->file
            ->relative("PriorityPropagation.grammar");
        $content = $this->file
            ->relative("PriorityPropagation.content");
    
        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar, $content);
        $result = $parser->parse();

        $this->assertSame($result->getThings(), [
            [
                0 => 2,
                1 => 'x',
            ]
        ]);
    }

    public function testRootSelection() : void {
        $file = $this->file
            ->relative("RootSelection.grammar");
        $content = $this->file
            ->relative("PriorityPropagation.content");
        
        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar, $content);
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
        $file = $this->file
            ->relative("QuantifierPriority.grammar");
        $content = $this->file
            ->relative("PriorityPropagation.content");
        
        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar, $content);
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
        $file = $this->file
            ->relative("PrevPriority.grammar");
        $content = $this->file
            ->relative("PrevPriority.content");

        $grammar = new \pharos\phathom\Grammar($file);
        $parser = new \pharos\phathom\Parser($grammar, $content);
        $result = $parser->parse();

        /* The [4] alternative of b must win. */
        $this->assertSame($result->getThings(), [
            [4, 'x'],
        ]);
    }
}