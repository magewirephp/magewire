<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright;

use Magewirephp\Magewire\Component;

class Ui extends Component
{
    public int $requests = 0;

    public string $draft = '';

    public function simulateRequest(): void
    {
        usleep(750_000);

        $this->requests++;
    }
}
