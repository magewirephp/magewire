<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action\Type;

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
     * @param string $property
     * @param $component
     * @return void
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
     * @param string $property
     * @param $value
     * @param Component $component
     * @return void
     * @throws ComponentException
     */
    public function set(string $property, $value, Component $component): void
    {
        if ($this->propertyHelper->containsDots($property)) {
            $transform = $this->propertyHelper->transformDots($property, $value, $component);

            $property = $transform['property'];
            $value    = $transform['data'];
        }

        // Transform a magic property value.
        if (is_string($value)
            && strrpos($value, '$') === 0
            && ($value = ltrim($value, '$'))
            && array_key_exists($value, $component->getPublicProperties())) {
            $value = $component->{$value};
        }

        $component->{$property} = $value;
    }

    /**
     * Magic method ($refresh).
     */
    public function refresh(): void //phpcs:ignore
    {
    }
}
