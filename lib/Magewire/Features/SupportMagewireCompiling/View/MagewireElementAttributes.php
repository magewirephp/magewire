<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magewirephp\Magewire\Support\DataArray;
use Magewirephp\Magewire\Support\Distributor;
use Magewirephp\Magewire\Support\Factory;
use Stringable;

/**
 * Categorized storage for Magewire element attributes.
 *
 * Separates attributes into three categories:
 * - bindings: Dynamic/bound attributes (:attr, @event).
 * - html: Static HTML attributes (class, id, data-*).
 * - properties: Component properties and config.
 * - magewire: Magewire properties (magewire:id, magewire:resolver).
 *
 * @method DataArray bindings() Returns dynamic bound attributes.
 * @method DataArray html() Returns static HTML attributes.
 * @method DataArray properties() Returns component settings.
 * @method DataArray magewire() Returns Magewire properties.
 */
class MagewireElementAttributes extends Distributor implements Stringable
{
    private DataArray|null $attributes = null;

    public function __construct(string|null $type = null)
    {
        parent::__construct($type ?? DataArray::class);
    }

    public function distribution(): DataArray
    {
        return $this->html();
    }

    public function distribute(array $data): static
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && is_array($value)) {
                $this->create($key)->fill($value);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        $distribution = $this->distribution()->recursive(fn (DataArray $item) => $item->all());

        if (! is_array($distribution)) {
            return '';
        }

        $parts = [];

        foreach ($distribution as $group => $subset) {
            if (empty($subset)) {
                continue;
            }

            $groupParts = [];

            foreach ($subset as $attribute => $value) {
                $keyQuoted = "'" . addslashes($attribute) . "'";

                if ($value === true) {
                    $groupParts[] = $keyQuoted . ' => true';
                    continue;
                }
                if ($value === false) {
                    $groupParts[] = $keyQuoted . ' => false';
                    continue;
                }
                if ($value === null) {
                    $groupParts[] = $keyQuoted . ' => null';
                    continue;
                }

                if ($this->isExpression($value)) {
                    $groupParts[] = $keyQuoted . ' => ' . $value;
                } else {
                    $escaped = addslashes($value);
                    $groupParts[] = $keyQuoted . " => '" . $escaped . "'";
                }
            }

            if (!empty($groupParts)) {
                $groupEscaped = "'" . addslashes($group) . "'";
                $parts[] = $groupEscaped . ' => [' . implode(', ', $groupParts) . ']';
            }
        }

        return implode(', ', $parts);
    }

    protected function isExpression(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $v = trim($value);

        return
            // Must start with $ or contain $ with operators.
            preg_match('/^\$/', $v) ||
            // Object/property access (strong indicator).
            str_contains($v, '->') || str_contains($v, '?->') ||
            // Static calls (strong indicator).
            str_contains($v, '::') ||
            // Variable array access.
            preg_match('/\$\w+\s*\[/', $v) ||
            // String concatenation with dots AND quotes (both must be present).
            (str_contains($v, '.') && (str_contains($v, "'") || str_contains($v, '"'))) ||
            // Null coalescing or ternary (strong indicator).
            str_contains($v, '??') || preg_match('/\s*\?\s*[^\s]/', $v) ||
            // Known PHP functions (strict - only at word boundaries).
            preg_match('/\b(json_encode|json_decode)\s*\(/', $v);
    }

    protected function create(string $name, array $arguments = []): DataArray
    {
        $this->attributes ??= Factory::create($this->type, ['name' => $name]);

        // Return the root data array, which is used as the HTML attributes collection.
        if ($name === 'html') {
            return $this->attributes;
        }

        return $this->attributes->subset($name);
    }
}
