<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Exception\ComponentHydrationException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class Hash implements HydratorInterface
{
    protected array $domHashes = [];

    /**
     * @throws ComponentHydrationException
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if (! $request->getFingerprint('id') || ! $request->getFingerprint('name')) {
            throw new ComponentHydrationException(__('Request fingerprint doesn\'t have all data available'));
        }

        if ($request->getServerMemo('htmlHash')) {
            $this->domHashes[$component->id] = $request->getServerMemo('htmlHash');
        }
    }

    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        if ($response->getRequest()->isSubsequent() && ! $component->canRender()) {
            // The browser still displays the HTML described by the existing hash.
            $response->effects['html'] = null;
            return;
        }

        $hash = $this->domHashes[$component->id] ?? null;
        $response->memo['htmlHash'] = hash('crc32b', $response->effects['html']);

        if (empty($response->effects['html']) || $hash === $response->memo['htmlHash']) {
            $response->effects['html'] = null;
        }
    }
}
