<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Flake;

use Magewirephp\Magewire\Component;

class Dialog extends Component
{
    public int|float $count = 0;
    public bool $test = false;
    public $upload = null;
    public string $title = '';

    public function increment()
    {
        $this->count++;
        $this->title = $this->title === 'A' ? 'B' : 'A';
    }
}
