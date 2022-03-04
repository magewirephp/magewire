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
 * @api
 */
interface ActionInterface
{
    /**
     * Handle update action.
     *
     * @param Component $component
     * @param array $payload
     *
     * @return mixed
     */
    public function handle(Component $component, array $payload);
}
