<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext\HandlerTypeConditions;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerType;

class ComponentHandler extends HandlerType
{
    public function onHook(string $hook): static
    {
        $this->data()->set('args.hooks', [], $hook);

        return $this;
    }

    /**
     * Limit the view instruction to apply only subsequent component requests.
     */
    public function onlyForSubsequent(): HandlerTypeConditions
    {
        return $this->conditionally()->if(function (Component $component) {
            return true;
        }, 'only-subsequent');
    }

    /**
     * Limit the view instruction to apply only preceding component requests.
     */
    public function onlyForPreceding(): HandlerTypeConditions
    {
        return $this->conditionally()->if(function (Component $component) {
            return true;
        }, 'only-preceding');
    }
}
