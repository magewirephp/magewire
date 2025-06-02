<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireLoaders;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use function Magewirephp\Magewire\map_with_keys;

class SupportMagewireLoaders extends ComponentHook
{
    function dehydrate(ComponentContext $context): void
    {
        $loader = $context->component->getLoader();

        if ($loader) {
            if (is_array($loader)) {
                $loader = map_with_keys(function ($value, $key) {
                    if (is_string($value)) {
                        $value = [$value];
                    }
                    if (is_array($value)) {
                        $value = array_map('__', array_filter($value, 'is_string'));
                    }

                    return [$key => $value];
                }, $loader);
            } elseif (is_string($loader)) {
                $loader = __($loader);
            }

            $context->pushEffect('loader', $loader);
        }
    }
}
