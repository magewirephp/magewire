<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoFlashMessages;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;

class SupportMagentoFlashMessages extends ComponentHook
{
    public function dehydrate(ComponentContext $context): void
    {
        $component = $this->component();

        if ($component && $component->magewireFlashMessages()->count() > 0) {
            $context->pushEffect('dispatches', [
                'name' => 'magewire:flash-messages:dispatch',
                'params' => $this->mapFlashMessages($component->magewireFlashMessages())
            ]);
        }
    }

    private function mapFlashMessages(MagewireFlashMessages $messages): array
    {
        return array_map(static function (FlashMessage $message) {
            return [
                'text' => $message->message()->render(),
                'type' => $message->type()
            ];
        }, $messages->fetch());
    }
}
