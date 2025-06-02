<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportValidation;

use Attribute;
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class BaseRule extends BaseValidate
{
    // This class is kept here for backwards compatibility...
}