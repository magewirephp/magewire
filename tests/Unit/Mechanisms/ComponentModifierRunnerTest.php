<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Mechanisms;

use InvalidArgumentException;
use Magewirephp\Magewire\Magewire\Playwright\Events\Basic;
use Magewirephp\Magewire\Magewire\Playwright\Events\ConfigureComponent;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\BlockMagewireArguments;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentModifiers\ComponentModifierContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentModifiers\ComponentModifierRunner;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentModifiers\ModifierInterface;
use Magewirephp\Magewire\Support\DataCollection\Filter;
use PHPUnit\Framework\TestCase;

class ComponentModifierRunnerTest extends TestCase
{
    public function test_it_leaves_arguments_without_modifiers_unchanged(): void
    {
        $arguments = $this->arguments(['loader' => 'Original']);

        $runner = new ComponentModifierRunner();
        $runner->run(new ComponentModifierContext(new Basic(), $arguments));

        self::assertSame('Original', $arguments->get('loader'));
    }

    public function test_it_modifies_any_argument_in_xml_order(): void
    {
        $component = new Basic();
        $arguments = $this->arguments([
            'modifiers' => [
                new ConfigureComponent(),
                new class implements ModifierInterface {
                    public function modify(ComponentModifierContext $context): void
                    {
                        $arguments = $context->arguments();
                        $arguments->merge(['custom' => (string) $arguments->get('loader')['onModifierAdded'][0]]);
                        $context->component()->scope = 'modified';
                    }
                }
            ],
            'listeners' => ['modifier:replace' => 'onLayoutOriginal'],
            'loader' => ['onClassKept' => 'Layout loading']
        ]);

        $runner = new ComponentModifierRunner();
        $runner->run(new ComponentModifierContext($component, $arguments));

        self::assertSame('onModifierAdded', $arguments->get('listeners')['modifier:resolved']);
        self::assertSame('onModifierReplacement', $arguments->get('listeners')['modifier:replace']);
        self::assertSame('Loading from PHP modifier', (string) $arguments->get('loader')['onClassKept'][0]);
        self::assertSame('Adding from PHP modifier', $arguments->get('custom'));
        self::assertSame('modified', $component->scope);
    }

    public function test_it_respects_conditions_in_the_modifier(): void
    {
        $component = new Basic();
        $component->scope = 'other';
        $arguments = $this->arguments([
            'modifiers' => [new ConfigureComponent()],
            'listeners' => ['modifier:replace' => 'onLayoutOriginal']
        ]);

        $runner = new ComponentModifierRunner();
        $runner->run(new ComponentModifierContext($component, $arguments));

        self::assertSame(['modifier:replace' => 'onLayoutOriginal'], $arguments->get('listeners'));
    }

    public function test_it_requires_an_array_of_modifiers(): void
    {
        $arguments = $this->arguments(['modifiers' => 'invalid']);

        $this->expectException(InvalidArgumentException::class);
        $runner = new ComponentModifierRunner();
        $runner->run(new ComponentModifierContext(new Basic(), $arguments));
    }

    public function test_it_rejects_an_invalid_modifier_entry(): void
    {
        $arguments = $this->arguments(['modifiers' => ['invalid' => new \stdClass()]]);

        $this->expectException(InvalidArgumentException::class);
        $runner = new ComponentModifierRunner();
        $runner->run(new ComponentModifierContext(new Basic(), $arguments));
    }

    private function arguments(array $values): BlockMagewireArguments
    {
        return new BlockMagewireArguments($this->createMock(Filter::class), $values);
    }
}
