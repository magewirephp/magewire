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
    public function component()
    {
        return $this->component;
    }

    /**
     * @deprecated Still undecided if this should be something that needs to go into the framework.
     */
    public function callMagewireComponentConstruct(...$params): void
    {
        if (method_exists($this, 'magewireComponentConstruct')) {
            $this->magewireComponentConstruct(...$params);
        }
    }

    /**
     * @deprecated Still undecided if this should be something that needs to go into the framework.
     */
    public function callMagewireComponentReconstruct(...$params): void
    {
        if (method_exists($this, 'magewireComponentReconstruct')) {
            $this->magewireComponentReconstruct(...$params);
        }
    }

    /**
     * @deprecated Has been replaced with the component method eliminating the get prefix.
     * @see self::$component()
     */
    public function getComponent()
    {
        return $this->component;
    }
}
