<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Helper\Functions as FunctionsHelper;
use Magewirephp\Magewire\Model\Action\CallMethod;
use Magewirephp\Magewire\Model\Action\SyncInput;
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

    //phpcs:ignore
    public function hydrate(Component $component, RequestInterface $request): void
    {
    }

    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        $loader = $component->getLoader();

        if ($loader) {
            if (is_array($loader)) {
                $loader = $this->functionsHelper->mapWithKeys(function ($value, $key) {
                    if (is_string($key) === false && is_string($value)) {
                        return [$value => true];
                    }
                    if (is_string($value)) {
                        $value = __($value);
                    }

                    return [$key => $value];
                }, $loader);
            }

            $response->effects['loader'] = $loader;
        }
    }
}
