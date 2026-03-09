<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\Scheduler;

use DateTime;

class Interval
{
    private string $unit = 'year';
    private DateTime|null $run = null;

    public function __construct(
        private int $interval = 0
    ) {
    }

    public function minutes(): static
    {
        $this->unit = 'minutes';
        return $this;
    }

    public function hours(): static
    {
        $this->unit = 'hours';
        return $this;
    }

    public function days(): static
    {
        $this->unit = 'days';
        return $this;
    }

    public function months(): static
    {
        $this->unit = 'months';
        return $this;
    }

    public function years(): static
    {
        $this->unit = 'years';
        return $this;
    }
}
