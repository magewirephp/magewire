<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireFlakes;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Component\FlakeFactory;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition\FlakeDefinition;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition\FlakeDefinitionMetadata;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition\FlakeDefinitionRegistry;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Layout\FlakeLayoutFactory;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespace;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespacePool;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakeRenderContext;
use PHPUnit\Framework\TestCase;

class FlakeFactoryIsolationTest extends TestCase
{
    public function test_presentation_flake_is_a_compiled_stateless_block_by_default(): void
    {
        $namespace = new FlakeNamespace('flake', 'magewire_flakes', 'magewire.flakes');
        $pool = new FlakeNamespacePool([$namespace]);
        $definition = new FlakeDefinition(
            $namespace,
            'card',
            Template::class,
            'Vendor_Module::flakes/card.phtml',
            new FlakeDefinitionMetadata()
        );
        $registry = $this->createMock(FlakeDefinitionRegistry::class);
        $registry->method('get')->with('flake', 'card')->willReturn($definition);
        $layoutFactory = $this->createMock(FlakeLayoutFactory::class);
        $factory = new FlakeFactory($pool, $registry, $layoutFactory);

        $context = $factory->createContextByName('card');

        self::assertNotFalse($context);
        self::assertTrue($context->data()['magewire:compile']);
        self::assertSame('flake', $context->data()['magewire:resolver']);
        self::assertSame('card', $context->data()['magewire:alias']);
        self::assertArrayNotHasKey('magewire', $context->data());
    }

    public function test_repeated_blocks_use_fresh_layouts_and_contexts(): void
    {
        $namespace = new FlakeNamespace('flake', 'magewire_flakes', 'magewire.flakes');
        $pool = new FlakeNamespacePool([$namespace]);
        $definition = new FlakeDefinition(
            $namespace,
            'card',
            Template::class,
            'Vendor_Module::flakes/card.phtml',
            new FlakeDefinitionMetadata()
        );
        $registry = $this->createMock(FlakeDefinitionRegistry::class);
        $registry->method('get')->with('flake', 'card')->willReturn($definition);
        $component = $this->createMock(Component::class);

        $firstContext = null;
        $secondContext = null;
        $firstBlock = $this->createMock(Template::class);
        $firstBlock->expects(self::once())->method('addData')->with(self::callback(
            static function (array $data) use (&$firstContext, $component): bool {
                $firstContext = $data['flake:context'];

                return $data['custom'] === 'first'
                    && $data['magewire'] === $component
                    && $data['magewire:compile'] === true
                    && $data['magewire:resolver'] === 'flake'
                    && $data['magewire:alias'] === 'card'
                    && $data['cache_key'] === 'flake-first-occurrence';
            }
        ));
        $firstBlock->expects(self::once())
            ->method('setNameInLayout')
            ->with('first-occurrence');
        $secondBlock = $this->createMock(Template::class);
        $secondBlock->expects(self::once())->method('addData')->with(self::callback(
            static function (array $data) use (&$secondContext, $component): bool {
                $secondContext = $data['flake:context'];

                return $data['custom'] === 'second'
                    && $data['magewire'] === $component
                    && $data['magewire:compile'] === true
                    && $data['magewire:resolver'] === 'flake'
                    && $data['magewire:alias'] === 'card'
                    && $data['cache_key'] === 'flake-second-occurrence';
            }
        ));
        $secondBlock->expects(self::once())
            ->method('setNameInLayout')
            ->with('second-occurrence');

        $firstLayout = $this->layoutFor($firstBlock);
        $secondLayout = $this->layoutFor($secondBlock);
        $layoutFactory = $this->createMock(FlakeLayoutFactory::class);
        $layoutFactory->expects(self::exactly(2))
            ->method('create')
            ->with($namespace)
            ->willReturnOnConsecutiveCalls($firstLayout, $secondLayout);
        $factory = new FlakeFactory($pool, $registry, $layoutFactory);

        $first = $factory->createByName('card', [
            'magewire' => $component,
            'magewire:name' => 'first-occurrence',
            'custom' => 'first'
        ]);
        $second = $factory->createByName('card', [
            'magewire' => $component,
            'magewire:name' => 'second-occurrence',
            'custom' => 'second'
        ]);

        self::assertSame($firstBlock, $first);
        self::assertSame($secondBlock, $second);
        self::assertNotSame($first, $second);
        self::assertInstanceOf(FlakeRenderContext::class, $firstContext);
        self::assertInstanceOf(FlakeRenderContext::class, $secondContext);
        self::assertNotSame($firstContext, $secondContext);
        self::assertSame('first-occurrence', $firstContext->id());
        self::assertSame('second-occurrence', $secondContext->id());
    }

    private function layoutFor(Template $block): LayoutInterface
    {
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getBlock')->with('card')->willReturn($block);
        $layout->method('getParentName')->with('card')->willReturn('magewire.flakes');

        return $layout;
    }
}
