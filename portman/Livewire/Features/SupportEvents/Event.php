<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportEvents;

use function Magewirephp\Magewire\app;

class Event extends \Livewire\Features\SupportEvents\Event
{
    public function serialize()
    {
        $output = ['name' => $this->name, 'params' => $this->params];

        if ($this->self) {
            $output['self'] = true;
        }
        if ($this->component) {
            $output['to'] = app(ComponentRegistry::class)->getName($this->component);
        }

        return $output;
    }
}
