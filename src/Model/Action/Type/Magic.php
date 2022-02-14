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
use Magewirephp\Magewire\Model\WireableInterface;

/**
 * Class Magic
 * @package Magewirephp\Magewire\Model\Action\Type
 */
class Magic
{
    /** @var PropertyHelper $propertyHelper */
    protected $propertyHelper;

    /**
     * Magic constructor.
     * @param PropertyHelper $propertyHelper
     */
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
     * @return mixed
     * @throws ComponentException
     */
    public function set(string $property, $value, Component $component)
    {
        if ($this->propertyHelper->containsDots($property)) {
            $transform = $this->propertyHelper->transformDots($property, $value, $component);

            $property = $transform['property'];
            $value    = $transform['data'];
        }

        // Transform a magic property value
        if (is_string($value) && strripos($value, '$') === 0 && ($value = ltrim($value, '$'))) {
            if (array_key_exists($value, $component->getPublicProperties())) {
                $value = $component->{$value};
            }
        }

        if ($component->{$property} instanceof WireableInterface) {
            return $component->{$property} = $component->{$property}->unwire($value);
        }

        return $component->{$property} = $value;
    }

    public function refresh(): void
    {
        return;
    }
}
