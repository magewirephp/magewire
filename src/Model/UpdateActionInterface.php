<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Exception;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\AcceptableException;

/**
 * @api
 */
interface UpdateActionInterface
{
    /**
     * Handle update action.
     *
     * @param Component $component
     * @param array $payload
     *
     * @return mixed
     * @throws AcceptableException
     * @throws Exception
     */
    public function handle(Component $component, array $payload);

    /**
     * Asks the ActionInterface if the given update can be handled yes/no.
     *
     * @param Component $component
     * @param string $type
     * @param array $payload
     * @return bool
     */
    public function belongsToMe(Component $component, string $type, array $payload): bool;
}
