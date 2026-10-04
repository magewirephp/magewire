<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeDefinitionInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Layout\FlakeLayoutFactory;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespacePool;

class FlakeDefinitionRegistry
{
    /** @var array<string, LayoutInterface> */
    private array $layouts = [];

    /** @var array<string, FlakeDefinitionInterface|false> */
    private array $definitions = [];

    /**
     * @param FlakeNamespacePool $namespacePool
     * @param FlakeLayoutFactory $layoutFactory
     */
    public function __construct(
        private readonly FlakeNamespacePool $namespacePool,
        private readonly FlakeLayoutFactory $layoutFactory
    ) {
    }

    /**
     * Return an immutable definition by namespace prefix and exact name.
     *
     * @param string $prefix
     * @param string $name
     */
    public function get(string $prefix, string $name): FlakeDefinitionInterface|null
    {
        $key = $prefix . ':' . $name;

        if (array_key_exists($key, $this->definitions)) {
            return $this->definitions[$key] ?: null;
        }

        $namespace = $this->namespacePool->get($prefix);
        $layout = $this->layouts[$prefix] ??= $this->layoutFactory->create($namespace);
        $block = $layout->getBlock($name);

        if (! $block instanceof AbstractBlock || $layout->getParentName($name) !== $namespace->registryRoot()) {
            $this->definitions[$key] = false;
            return null;
        }

        $metadata = $block->getData('flake');
        $template = method_exists($block, 'getTemplate') ? $block->getTemplate() : null;

        $definition = new FlakeDefinition(
            $namespace,
            $name,
            $block::class,
            is_string($template) && $template !== '' ? $template : null,
            FlakeDefinitionMetadata::fromArray(is_array($metadata) ? $metadata : [])
        );

        $this->definitions[$key] = $definition;

        return $definition;
    }
}
