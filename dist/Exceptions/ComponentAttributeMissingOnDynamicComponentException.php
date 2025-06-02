<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Exceptions;

class ComponentAttributeMissingOnDynamicComponentException extends \Exception
{
    use BypassViewHandler;
    public function __construct()
    {
        parent::__construct('Dynamic component tag is missing component attribute.');
    }
}