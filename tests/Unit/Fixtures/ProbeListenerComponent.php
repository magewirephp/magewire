<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use Magewirephp\Magewire\Component;

/**
 * A component listening to probe:evt with its onProbe() method, which a subclass declares.
 */
abstract class ProbeListenerComponent extends Component
{
    protected $listeners = ['probe:evt' => 'onProbe'];

    protected function getListeners(): array
    {
        return $this->listeners;
    }
}
