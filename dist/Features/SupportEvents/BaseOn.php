<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportEvents;

use Attribute;
use Illuminate\Support\Arr;
use Magewirephp\Magewire\Features\SupportAttributes\Attribute as LivewireAttribute;
use function Magewirephp\Magewire\store;
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class BaseOn extends LivewireAttribute
{
    public function __construct(public $event)
    {
    }
    public function boot()
    {
        foreach (Arr::wrap($this->event) as $event) {
            store($this->component)->push('listenersFromAttributes', $this->getName() ?? '$refresh', $event);
        }
    }
}