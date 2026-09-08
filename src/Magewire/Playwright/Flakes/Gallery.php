<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\Flakes;

use Magewirephp\Magewire\Component;

class Gallery extends Component
{
    public int $refreshCount = 0;

    /**
     * Re-render the host to exercise every nested Flake occurrence.
     *
     * @return void
     */
    public function refreshExamples(): void
    {
        $this->refreshCount++;
    }
}
