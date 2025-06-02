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

class MethodNotFoundException extends \Exception
{
    use BypassViewHandler;
    public function __construct($method)
    {
        parent::__construct("Unable to call component method. Public method [{$method}] not found on component");
    }
}