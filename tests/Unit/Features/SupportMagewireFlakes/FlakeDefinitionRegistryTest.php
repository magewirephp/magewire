<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireFlakes;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition\FlakeDefinitionRegistry;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Layout\FlakeLayoutFactory;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespace;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespacePool;
use PHPUnit\Framework\TestCase;

class FlakeDefinitionRegistryTest extends TestCase
{
    public function test_it_normalizes_and_caches_an_immutable_definition(): void
    {
        $namespace = new FlakeNamespace('flake', 'magewire_flakes', 'magewire.flakes');
        $pool = new FlakeNamespacePool([$namespace]);
        $block = $this->createMock(Template::class);
        $block->method('getData')->with('flake')->willReturn([
            'family' => 'card',
            'props' => ['tone' => 'neutral'],
            'children' => ['heading', '@html'],
            'aware' => ['density' => 'card.density']
        ]);
        $block->method('getTemplate')->willReturn('Vendor_Module::flakes/card.phtml');

        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getBlock')->with('card')->willReturn($block);
        $layout->method('getParentName')->with('card')->willReturn('magewire.flakes');

        $layoutFactory = $this->createMock(FlakeLayoutFactory::class);
        $layoutFactory->expects(self::once())->method('create')->with($namespace)->willReturn($layout);
        $registry = new FlakeDefinitionRegistry($pool, $layoutFactory);

        $definition = $registry->get('flake', 'card');

        self::assertNotNull($definition);
        self::assertSame($definition, $registry->get('flake', 'card'));
        self::assertSame('card', $definition->name());
        self::assertSame($block::class, $definition->blockClass());
        self::assertSame('Vendor_Module::flakes/card.phtml', $definition->template());
        self::assertSame('card', $definition->metadata()->family());
        self::assertSame(['tone' => 'neutral'], $definition->metadata()->props());
        self::assertSame(['heading', '@html'], $definition->metadata()->children());
        self::assertSame(['density' => 'card.density'], $definition->metadata()->aware());
    }

    public function test_it_rejects_a_block_outside_the_namespace_root(): void
    {
        $namespace = new FlakeNamespace('flake', 'magewire_flakes', 'magewire.flakes');
        $pool = new FlakeNamespacePool([$namespace]);
        $block = $this->createMock(Template::class);
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getBlock')->with('card')->willReturn($block);
        $layout->method('getParentName')->with('card')->willReturn('unrelated.container');

        $layoutFactory = $this->createMock(FlakeLayoutFactory::class);
        $layoutFactory->expects(self::once())->method('create')->willReturn($layout);
        $registry = new FlakeDefinitionRegistry($pool, $layoutFactory);

        self::assertNull($registry->get('flake', 'card'));
        self::assertNull($registry->get('flake', 'card'));
    }
}
