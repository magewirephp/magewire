<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportLockedProperties;

class CannotUpdateLockedPropertyException extends \Exception
{
    public function __construct(public $property)
    {
        parent::__construct('Cannot update locked property: [' . $this->property . ']');
    }
}