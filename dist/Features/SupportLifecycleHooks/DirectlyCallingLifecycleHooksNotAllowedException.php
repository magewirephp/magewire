<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportLifecycleHooks;

use Magewirephp\Magewire\Exceptions\BypassViewHandler;
class DirectlyCallingLifecycleHooksNotAllowedException extends \Exception
{
    use BypassViewHandler;
    public function __construct($method, $component)
    {
        parent::__construct("Unable to call lifecycle method [{$method}] directly on component: [{$component}]");
    }
}