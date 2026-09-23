<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Mechanisms;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\LayoutArgumentOverlay;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolver;
use PHPUnit\Framework\TestCase;

class LayoutArgumentOverlayTest extends TestCase
{
    private LayoutArgumentOverlay $layoutArgumentOverlay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layoutArgumentOverlay = new LayoutArgumentOverlay();
    }

    public function test_it_appends_list_values_and_replaces_or_removes_named_values(): void
    {
        self::assertSame(
            [
                'classAction',
                'save' => 'Layout saving',
                'layoutAction'
            ],
            $this->layoutArgumentOverlay->apply(
                [
                    'classAction',
                    'save' => 'Class saving',
                    'delete' => 'Class deleting'
                ],
                [
                    'layoutAction',
                    'save' => 'Layout saving',
                    'delete' => null
                ],
                [null]
            )
        );
    }

    public function test_it_preserves_false_when_the_consumer_only_removes_null(): void
    {
        self::assertSame(['save' => false], $this->layoutArgumentOverlay->apply(['save' => 'Class saving'], ['save' => false], [null]));
    }

    public function test_it_discards_removals_when_an_array_replaces_a_scalar(): void
    {
        self::assertSame(['save' => 'Layout saving'], $this->layoutArgumentOverlay->apply(false, ['save' => 'Layout saving', 'obsolete' => null], [null]));
    }

    public function test_it_uses_a_layout_scalar_even_when_it_is_null(): void
    {
        $component = $this->componentWithArguments(['loader' => null]);

        self::assertNull($this->layoutArgumentOverlay->value($component, 'loader', ['save' => 'Class saving']));
    }

    public function test_it_leaves_the_original_value_when_the_argument_is_absent(): void
    {
        $component = $this->componentWithArguments([]);

        self::assertSame(['save'], $this->layoutArgumentOverlay->value($component, 'loader', ['save']));
    }

    private function componentWithArguments(array $values): Component
    {
        $arguments = $this->createMock(MagewireArguments::class);
        $arguments->method('all')->willReturn($values);

        $resolver = $this->createMock(ComponentResolver::class);
        $resolver->method('arguments')->willReturn($arguments);

        $component = new class extends Component {};
        $component->magewireResolver($resolver);

        return $component;
    }
}
