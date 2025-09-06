<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleComponents\Synthesizers;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Synthesizers\Synth;

class DataObjectSynth extends Synth
{
    public static string $key = 'mdo';

    function __construct(
        private readonly DataObjectFactory $dataObjectFactory,
        $context,
        $path
    ) {
        parent::__construct($context, $path);
    }

    static function match($target)
    {
        return $target instanceof DataObject;
    }

    function dehydrate($target, $dehydrateChild)
    {
        $data = (array) $target;

        foreach ($target as $key => $child) {
            $data[$key] = $dehydrateChild($key, $child);
        }

        return [$data, []];
    }

    function hydrate($value, $meta, $hydrateChild)
    {
        $obj = $this->dataObjectFactory->create();

        foreach ($value as $key => $child) {
            $obj->setData($key, $hydrateChild($key, $child));
        }

        return $obj;
    }

    function set(DataObject $target, $key, $value)
    {
        return $target->setData($key, $value);
    }
}
