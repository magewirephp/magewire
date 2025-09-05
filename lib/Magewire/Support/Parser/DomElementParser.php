<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\Parser;

use Magewirephp\Magewire\Support\Concerns\WithFactory;
use Magewirephp\Magewire\Support\DataArray;
use Magewirephp\Magewire\Support\DataArrayFactory;
use Magewirephp\Magewire\Support\Parser;

class DomElementParser extends Parser
{
    use WithFactory;

    private DataArray|null $attributes = null;

    public function __construct(
        private readonly DataArrayFactory $attributesFactory
    ) {
        //
    }

    public function parse(string $content): self
    {
        if (empty(trim($content))) {
            return $this;
        }

        // Match attribute="value" or attribute='value' patterns
        preg_match_all('/([a-zA-Z0-9\-_:]+)=(["\'])(.*?)\2/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $this->attributes()->set($match[1], $match[3]);
        }

        return $this;
    }

    public function attributes(): DataArray
    {
        return $this->attributes ??= $this->attributesFactory->create();
    }
}
