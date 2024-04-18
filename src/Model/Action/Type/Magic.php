<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action\Type;

use Exception;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentException;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;

class Magic
{
    protected PropertyHelper $propertyHelper;

    public function __construct(
        PropertyHelper $propertyHelper
    ) {
        $this->propertyHelper = $propertyHelper;
    }

    /**
     * Magic method ($toggle) to toggle boolean properties.
     *
     * Example: <button wire:click($toggle('public-bool-property'))>Toggle</button>
     *
     * @throws ComponentException
     */
    public function toggle(string $property, $component): void
    {
        $this->set($property, !$component->{$property}, $component);
    }

    /**
     * Magic method ($set) to update the value of a property.
     *
     * Example: <button wire:click="$set('public-property', 'the-value')">Set</button>
     *
     * @throws ComponentException
     */
    public function set(string $property, $value, Component $component): void
    {
        try{
            // Transform a magic property value.
            if (is_string($value)
            && strrpos($value, '$') === 0
            && ($value = ltrim($value, '$'))
            && array_key_exists($value, $component->getPublicProperties())) {
                $value = $component->{$value};
            }

            $nested = $this->propertyHelper->containsDots($property);
            $transform = [];

            if ($nested) {
                $transform = $this->propertyHelper->transformDots($property, $value, $component);
                $property = $transform['property'];
                $value = $transform['data'];
            }

            // Try to run existing pre-assignment methods if they exist.
            $value = $this->updating($component, $property, $value);

            if ($nested) {
                $component->{ $transform['property'] } = $this->propertyHelper->assignViaDots($transform['path'], $value, $component->{ $transform['property'] });
            } else {
                $component->{ $property } = $value;
            }

            // Try to run post-assignment methods if they exist.
            $value = $this->updated($component, $property, $value);

            if ($nested) {
                $component->{ $transform['property'] } = $this->propertyHelper->assignViaDots($transform['path'], $value, $component->{ $transform['property'] });
            } else {
                $component->{ $property } = $value;
            }
        } catch (Exception $exception) {
            $this->logger->critical(
                sprintf('Magewire: Something went wrong while syncing property "%s" onto component "%s"', $property, $component->name),
                ['exception' => $exception]
            );

            return;
        }
    }

    /**
     * Magic method ($refresh).
     */
    public function refresh(): void //phpcs:ignore
    {
    }

    private function updating(Component $component, string $property, $value)
    {
        $methods = ['updating' . str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $property))), 'updating'];

        foreach ($methods as $method) {
            if (method_exists($component, $method)) {
                $value = $component->{$method}($value, $property);
            }
        }

        return $value;
    }

    private function updated(Component $component, string $property, $value)
    {
        $methods = ['updated', 'updated' . str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $property)))];

        foreach ($methods as $method) {
            if (method_exists($component, $method)) {
                $value = $component->{$method}($value, $property);
            }
        }

        return $value;
    }
}
