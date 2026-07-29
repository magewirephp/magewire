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
    /**
     * Livewire ships the trigger mode as a route default and a separate #[Defer] attribute,
     * neither of which exists in Magento. It travels as a mode instead, matching the values
     * the "magewire:component:lazy" layout argument accepts.
     */
    public function __construct(public $isolate = true, public $mode = 'on-intersect')
    {
    }
}