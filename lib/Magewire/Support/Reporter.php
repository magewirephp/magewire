<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

/**
 * @deprecated This is still a work in progress.
 */
class Reporter
{
    private array $actions = [];

    public function schedule(callable $action): Scheduler
    {
        $scheduler = Factory::create(Scheduler::class);

        $this->actions[] = function () use ($action, $scheduler) {
            // TBD.
        };

        return $scheduler;
    }
}
