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

class MissingRulesException extends \Exception
{
    use BypassViewHandler;
    public function __construct($instance)
    {
        $class = $instance::class;
        parent::__construct("Missing [\$rules/rules()] property/method on: [{$class}].");
    }
}