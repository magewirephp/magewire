<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Element;

use Magento\Framework\Escaper;
use Magewirephp\Magewire\Support\DataArray;
use Stringable;

class Attributes extends DataArray implements Stringable
{
    public function __construct(
        private Escaper $escaper,
        int $level = 0,
        string $name = 'root',
        DataArray|null $parent = null
    ) {
        parent::__construct($level, $name, $parent);
    }

    public function __toString(): string
    {
        $attributes = $this->all();

        if (empty($attributes)) {
            return '';
        }

        $parts = [];

        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = $name;
                }

                continue;
            }

            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            $parts[] = sprintf('%s="%s"', $name, $this->escaper->escapeHtmlAttr($value));
        }

        return empty($parts) ? '' : ' ' . implode(' ', $parts);
    }
}
