<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\CorruptPayloadException;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class Resolver implements HydratorInterface
{
    /**
     * @throws CorruptPayloadException
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if ($request->isSubsequent() && $request->getFingerprint('resolver') === null) {
            throw new CorruptPayloadException(get_class($component));
        }
    }

    // phpcs:ignore
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
    }
}
