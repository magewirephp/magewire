<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions;

use Illuminate\Support\Str;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Support\DataScope\Compiler;

class SupportMagewireViewInstructions extends ComponentHook
{
    public function __construct(
        private readonly Compiler\RecursiveArray $compiler
    ) {
        //
    }

    function dehydrate(ComponentContext $context): void
    {
        if ($this->getComponent()->viewInstructions()->isEmpty()) {
            return;
        }

        $context->pushEffect('magewire', array_map(function (ViewInstruction $instruction) {
            return $this->compiler
                ->use('component', $this->getComponent())
                ->use('instruction', $instruction)

                ->compile(
                    $instruction->data()
                        ->set('options', $instruction->getOptions(), null, false)
                        ->set('type', $instruction->getType())
                        ->set('action', Str::camel($instruction->getAction()))
                );
        }, $this->getComponent()->viewInstructions()->fetch()), 'view-instructions');
    }
}
