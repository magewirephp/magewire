<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magewirephp\Magewire\Component;

/**
 * @deprecated
 */
interface HydratorInterface
{
    /**
     * @param Component $component
     * @param RequestInterface $request
     */
    public function hydrate(Component $component, RequestInterface $request): void;

    /**
     * @param Component $component
     * @param ResponseInterface $response
     */
    public function dehydrate(Component $component, ResponseInterface $response): void;
}
