<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Helper\Functions as FunctionsHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class Loader implements HydratorInterface
{
    protected FunctionsHelper $functionsHelper;

    public function __construct(
        FunctionsHelper $functionsHelper
    ) {
        $this->functionsHelper = $functionsHelper;
    }

    public function hydrate(Component $component, RequestInterface $request): void //phpcs:ignore
    {
    }

    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        $loader = $component->getLoader();

        if ($loader) {
            if (is_array($loader)) {
                $loader = $this->functionsHelper->mapWithKeys(function ($value, $key) {
//                    if (! is_string($key) && is_string($value)) {
//                        return $loader;
//                    }
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

            $response->effects['loader'] = $loader;
        }
    }
}
