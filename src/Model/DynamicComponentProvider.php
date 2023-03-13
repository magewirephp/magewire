<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magewirephp\Magewire\Component\Dynamic;
use Magewirephp\Magewire\Exception\MissingComponentException;

class DynamicComponentProvider
{
    protected array $dynamicComponents;

    public function __construct(
        array $componentsPool
    ) {
        $this->dynamicComponents = $componentsPool;
    }

    public function getList(callable $filter = null, int $mode = ARRAY_FILTER_USE_BOTH): array
    {
        return $filter ? array_filter($this->dynamicComponents, $filter, $mode) : $this->dynamicComponents;
    }

    /**
     * @throws MissingComponentException
     */
    public function get(string $name)
    {
        $list = $this->getList(static function ($components, $namespace) use ($name) {
            return $name === $namespace && $components instanceof Dynamic;
        });

        if (count($list) === 0) {
            throw new MissingComponentException(__('Magewire component not found'));
        }

        return $list[$name];
    }
}
