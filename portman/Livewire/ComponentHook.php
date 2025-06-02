<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

abstract class ComponentHook extends \Livewire\ComponentHook
{
    function getComponent()
    {
        return $this->component;
    }

    function callMagewireConstruct(...$params) {
        if (method_exists($this, 'magewireConstruct')) {
            $this->magewireConstruct(...$params);
        }
    }
}
