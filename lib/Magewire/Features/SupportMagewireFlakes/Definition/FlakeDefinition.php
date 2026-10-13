<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition;

use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeDefinitionInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeNamespaceInterface;

class FlakeDefinition implements FlakeDefinitionInterface
{
    /**
     * @param class-string $blockClass
     */
    public function __construct(
        private readonly FlakeNamespaceInterface $namespace,
        private readonly string $name,
        private readonly string $blockClass,
        private readonly string|null $template,
        private readonly FlakeDefinitionMetadata $metadata
    ) {
    }

    /**
     * Return the namespace that owns this definition.
     */
    public function namespace(): FlakeNamespaceInterface
    {
        return $this->namespace;
    }

    /**
     * Return the exact component name from layout XML.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Return the layout block class used by the definition.
     *
     * @return class-string
     */
    public function blockClass(): string
    {
        return $this->blockClass;
    }

    /**
     * Return the configured PHTML template when available.
     */
    public function template(): string|null
    {
        return $this->template;
    }

    /**
     * Return normalized Flakes metadata.
     */
    public function metadata(): FlakeDefinitionMetadata
    {
        return $this->metadata;
    }
}
