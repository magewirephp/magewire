<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;

use Magento\Framework\DataObject;

class Effects extends DataObject
{
    function setData($key, $value = null): static
    {
        parent::setData($key, $value);

        return $this;
    }

    function getData($key = '', $index = null, $default = null)
    {
        return parent::getData($key) ?? $default;
    }

    function include($key, $value = null): static
    {
        return $this->setData($key, $value);
    }

    function exclude($key): static
    {
        return $this->unsetData($key);
    }
}
