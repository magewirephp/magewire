<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Concerns;

use Magento\Framework\DataObject;
use Magewirephp\Magewire\Drawer\Utils;

trait InteractsWithProperties
{
    function hasProperty($prop)
    {
        return property_exists($this, Utils::beforeFirstDot($prop));
    }

    function getPropertyValue($name)
    {
        $value = $this->{Utils::beforeFirstDot($name)};

        if (Utils::containsDots($name)) {
            return data_get($value, Utils::afterFirstDot($name));
        }

        return $value;
    }

    function fill($values)
    {
        $publicProperties = array_keys($this->all());

        if ($values instanceof DataObject) {
            $values = $values->toArray();
        }

        foreach ($values as $key => $value) {
            if (in_array(Utils::beforeFirstDot($key), $publicProperties)) {
                data_set($this, $key, $value);
            }
        }
    }

    function all()
    {
        return Utils::getPublicPropertiesDefinedOnSubclass($this);
    }
}
