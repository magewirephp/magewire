<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\DataScope\Compiler;

use Exception;
use Magewirephp\Magewire\Support\DataScope;

class RecursiveArray extends DataScope\Compiler
{
    public function compile(DataScope $data): array
    {
        $result = [];

        foreach ($data->fetch() as $key => $value) {
            if (is_array($value)) {
                $result[$key] = array_map(function ($item) {
                    if ($item instanceof DataScope) {
                        return $this->compile($item);
                    }

                    return $item;
                }, $value);
            } elseif ($value instanceof DataScope) {
                $result[$key] = $this->compile($value);
            } elseif (is_callable($value)) {
                try {
                    $result[$key] = $value(
                        array_reverse(
                            array_values($this->uses())
                        )
                    );
                } catch (Exception $exception) {
                    // WIP: logging needs to be implemented in a way...
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
