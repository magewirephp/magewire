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

class Memo extends DataObject
{
    function setData($key, $value = null): self
    {
        parent::setData($key, $value);
        return $this;
    }

    function getData($key = '', $index = null, $default = null)
    {
        return parent::getData($key) ?? $default;
    }
}
