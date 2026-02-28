<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;

class SupportMagewireBackwardsCompatibility extends ComponentHook
{
    public function dehydrate(ComponentContext $context): void
    {
        $bc = [
            'serverMemo' => [],
            'data' => 'path:$wire',
            '__livewire' => 'path:queuedUpdates'
        ];

        if ($context->hasEffect('evaluation')) {
            $bc['serverMemo']['evaluation'] = $context->getEffects()->getData('evaluation');
        }

        $isObsolete = $this->component->isObsolete ?? false;

        if ($isObsolete) {
            $bc['isObsolete'] = $isObsolete;
        }

        foreach ($bc as $ikey => $value) {
            $context->pushEffect('bc', $value, $ikey);
        }
    }
}
