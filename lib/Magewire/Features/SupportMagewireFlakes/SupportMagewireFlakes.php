<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Support\Concerns\AsDataObject;

use function Magewirephp\Magewire\on;

class SupportMagewireFlakes extends ComponentHook
{
    use AsDataObject;

    public function provide(): void
    {
        on('hydrate', static function (Component $component, array $memo) {
            $block = $component->magewireBlock();

            if (is_array($memo['flake'] ?? null)) {
                $block->setData('magewire:flake', $memo['flake']);
            }
        });

        on('dehydrate', static function (Component $component, ComponentContext $context) {
            $metadata = $component->magewireBlock()->getData('magewire:flake');

            if (is_array($metadata) && is_array($metadata['element'] ?? null)) {
                $context->pushMemo('flake', $metadata['element'], 'element');
            }
        });
    }
}
