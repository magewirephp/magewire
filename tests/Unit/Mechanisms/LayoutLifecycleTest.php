<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Mechanisms;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout\LayoutLifecycle;
use PHPUnit\Framework\TestCase;

class LayoutLifecycleTest extends TestCase
{
    private LayoutLifecycle $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lifecycle = new LayoutLifecycle();
    }

    /**
     * Magento renders a complex flash message with an anonymous block after the layout, so the
     * stack is empty and its route is "0", which PHP stores as the int key 0.
     */
    public function test_an_anonymous_block_rendered_at_the_root_has_no_closest_component(): void
    {
        $message = $this->block(null);

        $this->lifecycle->push($message);

        self::assertNull($this->lifecycle->closestComponent($message));
        self::assertNull($this->lifecycle->componentFor($message));
    }

    public function test_every_anonymous_root_block_resolves_not_only_the_first(): void
    {
        $first = $this->block(null);
        $second = $this->block(null);

        $this->lifecycle->push($first)->pop();
        $this->lifecycle->push($second);

        self::assertNull($this->lifecycle->closestComponent($second));
    }

    public function test_a_component_bound_to_an_anonymous_root_block_finds_its_block(): void
    {
        $block = $this->block(null);
        $component = $this->component();

        $this->lifecycle->push($block)->bind($component);

        self::assertSame($block, $this->lifecycle->blockFor($component));
        self::assertNull($this->lifecycle->parentComponent($component));
    }

    public function test_an_anonymous_block_under_a_component_still_resolves_to_it(): void
    {
        $parent = $this->block('checkout.main');
        $child = $this->block(null);
        $component = $this->component();

        $this->lifecycle->push($parent)->bind($component);
        $this->lifecycle->push($child);

        self::assertSame($component, $this->lifecycle->closestComponent($child));
    }

    private function block(string|null $nameInLayout): AbstractBlock
    {
        $block = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNameInLayout', 'getData'])
            ->getMock();
        $block->method('getNameInLayout')->willReturn($nameInLayout);
        $block->method('getData')->willReturn(null);

        return $block;
    }

    private function component(): Component
    {
        return new class extends Component {};
    }
}
