<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\AbstractBlock;

abstract class MagewireArguments extends DataObject
{
    private bool $assembled = false;

    private array $groups = [];

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

        // Assemble public arguments.
        $this->addData($this->filterPublicArguments($block));
        // Assemble private group arguments.
        $this->groups = $this->filterGroupArguments($block);

        $this->assembled = true;

        return $this;
    }

    public function reassemble(AbstractBlock $block): static
    {
        return $this->assemble($block, true);
    }

    public function __call($method, $args)
    {
        switch (substr((string) $method, 0, 3)) {
            case 'get':
                $key = $this->_underscore(substr($method, 3));
                $index = $args[0] ?? null;

                return $this->getData($key, $index);
            case 'set':
                $key = $this->_underscore(substr($method, 3));
                $value = $args[0] ?? null;

                return $this->setData($key, $value);
            case 'has':
                $key = $this->_underscore(substr($method, 3));

                return isset($this->_data[$key]);
        }

        throw new LocalizedException(
            new Phrase('Invalid method %1::%2', [get_class($this), $method])
        );
    }

    /**
     * Returns mount arguments.
     */
    public function forMount(): array
    {
        return array_merge($this->toParams(), $this->forGroup('mount'));
    }

    /**
     * Returns arguments for the given group.
     */
    public function forGroup(string $name, array $default = []): array
    {
        return $this->groups[$name] ?? $default;
    }

    /**
     * Besides the "toArray" method, this method ensures that we have the flexibility to implement
     * different logic for the parameters in the future, compared to the "toArray" method.
     * For now, this function primarily serves as a precautionary measure.
     */
    public function toParams(): array
    {
        return $this->toArray();
    }

    public function isLazy(): bool
    {
        return (bool) $this->getData('lazy');
    }

    protected function filterPublicArguments(AbstractBlock $block): array
    {
        $arguments = array_filter($block->getData(), function ($key) {
            return str_starts_with($key, 'magewire.');
        }, ARRAY_FILTER_USE_KEY);

        // Remove the "magewire." prefix from the extracted arguments to create new non-prefixed arguments.
        return array_combine(array_map(function($key) {
            return substr($key, 9);
        }, array_keys($arguments)), array_values($arguments));
    }

    protected function filterGroupArguments(AbstractBlock $block): array
    {
        $arguments = array_filter($block->getData(), function ($key) {
            return str_starts_with($key, 'magewire:');
        }, ARRAY_FILTER_USE_KEY);

        foreach ($arguments as $key => $value) {
            if (preg_match('/^magewire:([^:]+):([^:]+)/', $key, $matches)) {
                $groups[$matches[1]][$matches[2]] = $value;
            }
        }

        return $groups ?? [];
    }
}
