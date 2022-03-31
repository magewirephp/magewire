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
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostDeployment implements HydratorInterface
{
    public const DEPLOYMENT_INVALIDATION_HASH = 'acj';

    /**
     * @inheritdoc
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if ($request->isSubsequent()) {
            if (!isset($request->fingerprint['v'])) return;

            if ($v = $request->fingerprint['v']) {
                if ($v != self::DEPLOYMENT_INVALIDATION_HASH) {
                    throw new HttpException(419, 'New deployment contains changes to Magewire that have invalidated currently open browser pages.');
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        if ($response->getRequest()->isPreceding()) {
            $response->fingerprint['v'] = self::DEPLOYMENT_INVALIDATION_HASH;
        }
    }
}
