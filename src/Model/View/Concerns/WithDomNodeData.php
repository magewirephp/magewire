<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Concerns;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\DomElementData;
use Magewirephp\Magewire\Support\DataArray;
use Magewirephp\Magewire\Support\DataCollection;
use Magewirephp\Magewire\Support\Distributor;
use Magewirephp\Magewire\Support\Factory;

trait WithDomNodeData
{
    private Distributor|null $data = null;
    private DataArray|null $dictionary = null;

    public function dictionary(): DataCollection
    {
        return $this->dictionary ??= Factory::create(DataCollection::class);
    }

    /**
     * @return DataArray
     */
    public function attributes(): DataArray
    {
        return $this->data()->attributes();
    }

    /**
     * Returns the element properties/settings.
     */
    public function properties(): DataArray
    {
        return $this->data()->properties();
    }

    public function prop(string $name, mixed $default = null)
    {
        return $this->properties()->get($name, $default);
    }

    public function data(): DomElementData
    {
        return $this->data ??= Factory::create(DomElementData::class);
    }
}
