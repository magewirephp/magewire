<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Component;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeRenderContextInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeRendererInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition\FlakeDefinitionRegistry;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Layout\FlakeLayoutFactory;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespacePool;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakeAttributeBag;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakePropBag;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakeRenderContext;
use Magewirephp\Magewire\Support\Factory;
use Magewirephp\Magewire\Support\Random;
use RuntimeException;

class FlakeFactory implements FlakeRendererInterface
{
    /**
     * @param FlakeNamespacePool $namespacePool
     * @param FlakeDefinitionRegistry $definitionRegistry
     * @param FlakeLayoutFactory $layoutFactory
     * @param string $namespace
     */
    public function __construct(
        private readonly FlakeNamespacePool $namespacePool,
        private readonly FlakeDefinitionRegistry $definitionRegistry,
        private readonly FlakeLayoutFactory $layoutFactory,
        private readonly string $namespace = 'flake'
    ) {
    }

    /**
     * Create an empty Magewire component for backwards-compatible explicit use.
     *
     * Presentation Flakes no longer call this automatically. A Flake only owns
     * a Magewire lifecycle when its layout definition binds a `magewire`
     * argument or a caller supplies one explicitly.
     *
     * @param array<string, mixed> $arguments
     */
    public function create(array $arguments = []): Component
    {
        return Factory::create(Flake::class, $arguments);
    }

    /**
     * Create and return a new compiled block for a named Flake.
     *
     * The block remains stateless unless its layout definition or the supplied
     * data explicitly binds a Magewire component.
     *
     * @param string $name
     * @param array<string, mixed> $data
     */
    public function createByName(string $name, array $data = []): AbstractBlock|false
    {
        $context = $this->createContextByName($name, $data);

        return $context === false ? false : $this->createBlock($context);
    }

    /**
     * Create an isolated occurrence context for a named Flake.
     *
     * @param string $name
     * @param array<string, mixed> $data
     * @param array<string, mixed> $props
     * @param array<string, mixed> $attributes
     */
    public function createContextByName(
        string $name,
        array $data = [],
        array $props = [],
        array $attributes = []
    ): FlakeRenderContextInterface|false
    {
        $definition = $this->definitionRegistry->get($this->namespace, $name);

        if ($definition === null) {
            return false;
        }

        $data['magewire:compile'] ??= true;
        $data['magewire:resolver'] ??= $this->namespace;
        $data['magewire:alias'] ??= $name;
        $data['magewire:name'] ??= Random::alphabetical(10);

        return new FlakeRenderContext(
            (string) $data['magewire:name'],
            $this->namespacePool->get($this->namespace),
            $definition,
            $data,
            new FlakePropBag($props),
            new FlakeAttributeBag($attributes)
        );
    }

    /**
     * Render one isolated Flake occurrence.
     *
     * @param FlakeRenderContextInterface $context
     * @throws RuntimeException When the definition disappears from the fresh layout.
     */
    public function render(FlakeRenderContextInterface $context): string
    {
        $block = $this->createBlock($context);

        if ($block === false) {
            throw new RuntimeException(sprintf(
                'Flake block "%s" is missing from namespace "%s".',
                $context->definition()->name(),
                $context->namespace()->prefix()
            ));
        }

        return $block->toHtml();
    }

    /**
     * Build a fresh block for one occurrence.
     *
     * @param FlakeRenderContextInterface $context
     */
    private function createBlock(FlakeRenderContextInterface $context): AbstractBlock|false
    {
        $layout = $this->layoutFactory->create($context->namespace());
        $name = $context->definition()->name();
        $block = $layout->getBlock($name);

        if (! $block instanceof AbstractBlock
            || $layout->getParentName($name) !== $context->namespace()->registryRoot()
        ) {
            return false;
        }

        $cacheKey = preg_replace(
            '/[^a-z0-9\-_]/i',
            '-',
            $context->namespace()->prefix() . '-' . $context->id()
        );
        $block->addData(array_merge($context->data(), [
            'cache_key' => $cacheKey,
            'flake:context' => $context
        ]));
        $block->setNameInLayout($context->id());

        return $block;
    }
}
