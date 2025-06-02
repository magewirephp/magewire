<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser;

use Magewirephp\Magewire\Support\DataArray;

class FunctionArguments extends DataArray
{
    public function render(string $format = '$value'): string
    {
        $values = array_map(function($value) {
            // Check if the value is a string and if it's wrapped in single quotes
            if (is_string($value) && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
                // Remove the quotes from the string
                return substr($value, 1, strlen($value) - 2);
            }
            return $value;
        }, $this->values());

        return implode(', ', array_map(fn($k, $v) => str_replace(['$key', '$value'], [$k, var_export($v, true)], $format), $this->keys(), $values));
    }

    public function renderWithNames(): string
    {
        return $this->render('$key: $value');
    }
}
