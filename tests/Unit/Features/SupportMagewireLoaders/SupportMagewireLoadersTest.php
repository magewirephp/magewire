<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireLoaders;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireLoaders\SupportMagewireLoaders;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext\Effects;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext\Memo;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolver;
use PHPUnit\Framework\TestCase;

class SupportMagewireLoadersTest extends TestCase
{
    public function test_it_dehydrates_the_layout_overlay_without_changing_the_component_loader(): void
    {
        $component = new class extends Component {
            protected $loader = [
                'save' => 'Class saving',
                'delete' => 'Class deleting'
            ];
        };

        $arguments = $this->createMock(MagewireArguments::class);
        $arguments
            ->method('all')
            ->willReturn(['loader' => [
                'save' => 'Layout saving',
                'delete' => null,
                'publish' => 'Layout publishing'
            ]]);

        $resolver = $this->createMock(ComponentResolver::class);
        $resolver->method('arguments')->willReturn($arguments);
        $component->magewireResolver($resolver);

        $context = new ComponentContext(null, $component, true, new Effects(), new Memo());
        $hook = new SupportMagewireLoaders();
        $hook->setComponent($component);
        $hook->dehydrate($context);

        $loader = $context->getEffects()->getData('loader')[0];

        self::assertSame(['save', 'publish'], array_keys($loader));
        self::assertSame('Layout saving', (string) $loader['save'][0]);
        self::assertSame('Layout publishing', (string) $loader['publish'][0]);
        self::assertSame(['save' => 'Class saving', 'delete' => 'Class deleting'], $component->getLoader());
    }
}
