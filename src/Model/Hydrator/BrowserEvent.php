<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class BrowserEvent implements HydratorInterface
{
    //phpcs:ignore
    public function hydrate(Component $component, RequestInterface $request): void
    {
    }

    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        $browserEvents = $component->getBrowserEvents();

        if (!empty($browserEvents)) {
            $response->effects['dispatches'] = $browserEvents;
        }
    }
}
