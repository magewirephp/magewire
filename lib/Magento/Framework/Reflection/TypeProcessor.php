<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\Framework\Reflection;

use Laminas\Code\Reflection\ParameterReflection;

class TypeProcessor extends \Magento\Framework\Reflection\TypeProcessor
{
    public function getParamType(ParameterReflection $param): array
    {
        return [];
    }
}
