<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportLazyLoading;

use Magewirephp\Magewire\Features\SupportAttributes\Attribute as LivewireAttribute;
#[\Attribute(\Attribute::TARGET_CLASS)]
class BaseLazy extends LivewireAttribute
{
    public function __construct(public $isolate = true)
    {
    }
}