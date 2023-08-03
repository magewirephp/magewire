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
     * Runs before (planning stage) a individual action update is beind handled. Inspection is responsible
     * for allowing updates for the current type to proceed or not. In other words, when returning false,
     * both the handle and evaluate methods won't be able to run.
     *
     * @param array $updates // specific updates only for this action type.
     */
    public function inspect(Component $component, array $updates): bool;

    /**
     * Handle update action (execution stage).
     */
    public function handle(Component $component, array $payload);

    /**
     * Runs after (evaluation stage) a individual action update is beind handled.
     *
     * @param array $updates // specific updates only for this action type.
     */
    public function evaluate(Component $component, array $updates);
}
