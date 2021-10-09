<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action\Type;

use Magewirephp\Magewire\Helper\Property as PropertyHelper;

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
     */
    public function toggle(string $property, $component): void
    {
        $this->set($property, !$component->{$property}, $component);
    }

    /**
     * Magic method ($set) to update the value of a property.
     *
     * Example: <button wire:click($set('public-property', 'the-value'))>Set</button>
     *
     * @param string $property
     * @param $value
     * @param $component
     * @return void
     */
    public function set(string $property, $value, $component): void
    {
        if ($this->propertyHelper->containsDots($property)) {
            $transform = $this->propertyHelper->transformDots($property, $value, $component);

            // Re-assign original method properties
            $property = $transform['property'];
            $value = $transform['value'];
        }

        $component->assign($property, $value);
    }

    public function refresh(): void
    {
        return;
    }
}
