<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentHydrationException;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

/**
 * Class Hash.
 */
class Hash implements HydratorInterface
{
    /** @var array */
    protected $domHashes = [];

    /**
     * @inheritdoc
     *
     * @throws ComponentHydrationException
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if (!isset($request->fingerprint['id'], $request->fingerprint['name'])) {
            throw new ComponentHydrationException(__('Request fingerprint doesn\'t have all data available'));
        }

        $component->id = $request->fingerprint['id'];
        $component->name = $request->fingerprint['name'];

        if (isset($request->memo['htmlHash'])) {
            $this->domHashes[$component->id] = $request->memo['htmlHash'];
        }
    }

    /**
     * @inheritdoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        $hash = $this->domHashes[$component->id] ?? null;
        $response->memo['htmlHash'] = hash('crc32b', $response->effects['html']);

        if (empty($response->effects['html']) || $hash === $response->memo['htmlHash']) {
            $response->effects['html'] = null;
        }
    }
}
