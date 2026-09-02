<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition;

class FlakeDefinitionMetadata
{
    /**
     * @param array<string, mixed> $props
     * @param list<string> $children
     * @param array<string, string> $aware
     */
    public function __construct(
        private readonly string|null $family = null,
        private readonly array $props = [],
        private readonly array $children = [],
        private readonly array $aware = []
    ) {
    }

    /**
     * Normalize the nested `flake` block argument.
     *
     * @param array<string, mixed> $metadata
     */
    public static function fromArray(array $metadata): self
    {
        $family = is_string($metadata['family'] ?? null) && $metadata['family'] !== '' ? $metadata['family'] : null;
        $props = is_array($metadata['props'] ?? null) ? $metadata['props'] : [];
        $children = is_array($metadata['children'] ?? null) ? array_values(array_filter($metadata['children'], 'is_string')) : [];
        $aware = is_array($metadata['aware'] ?? null) ? array_filter($metadata['aware'], 'is_string') : [];

        return new self($family, $props, $children, $aware);
    }

    /**
     * Return the declared component family.
     */
    public function family(): string|null
    {
        return $this->family;
    }

    /**
     * Return declared prop defaults.
     *
     * @return array<string, mixed>
     */
    public function props(): array
    {
        return $this->props;
    }

    /**
     * Return unordered child-policy selectors.
     *
     * @return list<string>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * Return aware prop mappings.
     *
     * @return array<string, string>
     */
    public function aware(): array
    {
        return $this->aware;
    }

    /**
     * Determine whether a definition opted into Flakes metadata.
     */
    public function isEmpty(): bool
    {
        return $this->family === null && $this->props === [] && $this->children === [] && $this->aware === [];
    }
}
