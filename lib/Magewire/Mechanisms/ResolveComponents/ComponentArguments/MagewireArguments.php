<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Support\DataCollection;
use Magewirephp\Magewire\Support\Str;

abstract class MagewireArguments extends DataCollection
{
    private bool $assembled = false;

    /**
     * Argument assembly is based on a pattern where only values prefixed with "magewire." are accepted.
     * This is because block data can contain information not intended for the Magewire component,
     * but still necessary for supporting the block itself.
     */
    public function assemble(AbstractBlock $block, bool $force = false): static
    {
        if (! $force && $this->assembled) {
            return $this;
        }

        $this->destroy();

        $this->assembleComponentArguments($block);
        $this->assemblePublicArguments($block);
        $this->assembleGroupArguments($block);

        $this->assembled = true;
        return $this;
    }

    public function reassemble(AbstractBlock $block): static
    {
        return $this->assemble($block, true);
    }

    /**
     * Returns mount arguments.
     */
    public function forMount(): DataCollection
    {
        return $this->forGroup('mount');
    }

    /**
     * Returns arguments for the given group.
     */
    public function forGroup(string $name): DataCollection
    {
        return $this->subset('groups')->subset($name);
    }

    /**
     * Besides the "toArray" method, this method ensures that we have the flexibility to implement
     * different logic for the parameters in the future, compared to the "toArray" method.
     * For now, this function primarily serves as a precautionary measure.
     */
    public function toParams(): array
    {
        return $this->all();
    }

    public function isLazy(): bool
    {
        return (bool) $this->forGroup('component')->get('lazy', false);
    }

    protected function assembleComponentArguments(AbstractBlock $block): static
    {
        $arguments = array_filter(
            $block->getData(),
            static function ($key) {
                return str_starts_with($key, 'magewire:') && substr_count($key, ':') === 1;
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($arguments as $key => $value) {
            $name = explode(':', $key, 2)[1];

            $this->set(Str::camel($name), $value);
        }

        return $this;
    }

    protected function assemblePublicArguments(AbstractBlock $block): static
    {
        $arguments = array_filter(
            $block->getData(),
            static function ($key) {
                return str_starts_with($key, 'magewire.');
            },
            ARRAY_FILTER_USE_KEY
        );

        // Remove the "magewire." prefix and convert a kebab-case to camelCase
        $arguments = array_combine(array_map(static function ($key) {
            return Str::camel(substr($key, 9));
        }, array_keys($arguments)), array_values($arguments));

        $this->subset('public')->fill($arguments);
        return $this;
    }

    protected function assembleGroupArguments(AbstractBlock $block): static
    {
        $arguments = array_filter(
            $block->getData(),
            static function ($key) {
                return str_starts_with($key, 'magewire:');
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($arguments as $key => $value) {
            if (! preg_match('/^magewire:([^:]+):([^:]+)/', $key, $matches)) {
                continue;
            }

            $this->subset('groups')->subset($matches[1])->set(Str::camel($matches[2]), $value);
        }

        return $this;
    }
}
