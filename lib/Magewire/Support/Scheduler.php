<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Magewirephp\Magewire\Support\Concerns\WithFactory;
use Magewirephp\Magewire\Support\Scheduler\Interval;

/**
 * @deprecated This is still work in progress.
 */
class Scheduler
{
    use WithFactory;

    private array $conditions = [];

    public function every($interval): Interval
    {
        // TBD.

        return $this->newTypeInstance(Interval::class, ['interval' => $interval]);
    }

    public function daily(): static
    {
        $this->every(1)->days();
        return $this;
    }

    public function monthly(): Interval
    {
        return $this->every(1)->months();
    }

    public function yearly($interval): Interval
    {
        return $this->every(1)->years();
    }

    public function when(callable $condition): Conditions
    {
        return $this->conditions()->if($condition);
    }

    public function unless(callable $condition): Conditions
    {
        return $this->when(static fn (...$args) => ! $condition(...$args));
    }

    protected function conditions(): Conditions
    {
        return $this->conditions ??= $this->newTypeInstance(Conditions::class);
    }
}
