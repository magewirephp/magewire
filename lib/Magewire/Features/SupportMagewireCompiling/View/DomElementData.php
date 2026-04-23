<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magewirephp\Magewire\Model\View\Element\Attributes;
use Magewirephp\Magewire\Model\View\Element\Properties;
use Magewirephp\Magewire\Support\DataArray;
use Magewirephp\Magewire\Support\Distributor;

/**
 * Categorized storage for Magewire element attributes.
 *
 * Separates attributes into three categories:
 * - attributes: Dynamic/bound attributes (:attr, @event).
 * - properties: Component properties and config.
 * - magewire: Magewire properties (magewire:id, magewire:resolver).
 *
 * @method DataArray data() Returns parent data object.
 * @method DataArray attributes() Returns dynamic bound attributes.
 * @method DataArray properties() Returns component properties/settings.
 * @method DataArray magewire() Returns Magewire properties.
 */
class DomElementData extends Distributor
{
    public function __construct(
        string|null $type = null,
        protected array $mapping = []
    ) {
        $mapping = array_merge([
            'attributes' => Attributes::class,
            'properties' => Properties::class
        ], $mapping);

        parent::__construct($type ?? DataArray::class, $mapping);
    }

    public function distribution(): DataArray
    {
        return $this->data();
    }

    /**
     * Distribute keyed arrays into individual data types.
     *
     * Iterates through the provided data and creates a separate slot for each string key
     * that contains an array value. Each slot is then populated with its corresponding array content.
     *
     * Non-string keys and non-array values are ignored during distribution.
     *
     * @example
     *   $slots->distribute([
     *       'header' => ['title' => 'Welcome', 'subtitle' => 'Hello World'],
     *       'sidebar' => ['widgets' => [...]]
     *   ]);
     */
    public function distribute(array $data): static
    {
        foreach ($data as $key => $value) {
            if (! ( is_string($key) && is_array($value) )) {
                continue;
            }

            $this->create($key)->fill($value);
        }

        return $this;
    }

    protected function create(string|null $type = null, array $arguments = []): object
    {
        if ($type === 'data') {
            return parent::create($type, $arguments);
        }

        return $this->data()->subset($type, $this->resolve($type), $arguments);
    }
}
