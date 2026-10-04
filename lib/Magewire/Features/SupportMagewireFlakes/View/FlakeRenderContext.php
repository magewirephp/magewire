<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View;

use InvalidArgumentException;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeDefinitionInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeNamespaceInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeRenderContextInterface;

class FlakeRenderContext implements FlakeRenderContextInterface
{
    private readonly FlakePropBag $props;

    private readonly FlakeAttributeBag $attributes;

    private readonly FlakeSlotBag $slots;

    private readonly FlakeAncestorFrames $ancestors;

    /**
     * @param string $id
     * @param FlakeNamespaceInterface $namespace
     * @param FlakeDefinitionInterface $definition
     * @param array<string, mixed> $data
     * @param FlakePropBag|null $props
     * @param FlakeAttributeBag|null $attributes
     * @param FlakeSlotBag|null $slots
     * @param FlakeAncestorFrames|null $ancestors
     */
    public function __construct(
        private readonly string $id,
        private readonly FlakeNamespaceInterface $namespace,
        private readonly FlakeDefinitionInterface $definition,
        private readonly array $data = [],
        FlakePropBag|null $props = null,
        FlakeAttributeBag|null $attributes = null,
        FlakeSlotBag|null $slots = null,
        FlakeAncestorFrames|null $ancestors = null
    ) {
        if ($id === '') {
            throw new InvalidArgumentException('A Flake render context requires an occurrence identifier.');
        }

        if ($namespace->prefix() !== $definition->namespace()->prefix()) {
            throw new InvalidArgumentException('The Flake definition does not belong to the render namespace.');
        }

        $this->props = $props ?? new FlakePropBag();
        $this->attributes = $attributes ?? new FlakeAttributeBag();
        $this->slots = $slots ?? new FlakeSlotBag();
        $this->ancestors = $ancestors ?? new FlakeAncestorFrames();
    }

    /**
     * Return the occurrence identifier.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Return the namespace selected for this occurrence.
     */
    public function namespace(): FlakeNamespaceInterface
    {
        return $this->namespace;
    }

    /**
     * Return the immutable component definition.
     */
    public function definition(): FlakeDefinitionInterface
    {
        return $this->definition;
    }

    /**
     * Return occurrence-local block data.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Return occurrence-local properties.
     */
    public function props(): FlakePropBag
    {
        return $this->props;
    }

    /**
     * Return occurrence-local HTML attributes.
     */
    public function attributes(): FlakeAttributeBag
    {
        return $this->attributes;
    }

    /**
     * Return occurrence-local slots.
     */
    public function slots(): FlakeSlotBag
    {
        return $this->slots;
    }

    /**
     * Return the ancestor frames visible to this occurrence.
     */
    public function ancestors(): FlakeAncestorFrames
    {
        return $this->ancestors;
    }
}
