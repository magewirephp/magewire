<?php

namespace Magewirephp\Magewire\Features\SupportLazyLoading;

class BaseLazy extends \Livewire\Features\SupportLazyLoading\BaseLazy
{
    /**
     * Livewire ships the trigger mode as a route default and a separate #[Defer] attribute,
     * neither of which exists in Magento. It travels as a mode instead, matching the values
     * the "magewire:component:lazy" layout argument accepts.
     */
    public function __construct(
        public $isolate = true,
        public $mode = 'on-intersect'
    ) {}
}
