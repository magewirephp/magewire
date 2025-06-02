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
    function dehydrate(ComponentContext $context): void
    {
        if ($this->component->hasFlashMessages()) {
            $context->pushEffect('dispatches', [
                'name' => 'magewire:flash-messages:dispatch',
                'params' => $this->mapFlashMessages($this->component->getFlashMessages())
            ]);
        }
    }

    /**
     * @param array<int, FlashMessage> $messages
     */
    private function mapFlashMessages(array $messages): array
    {
        return array_map(static function (FlashMessage $message) {
            return [
                'text' => $message->getMessage()->render(),
                'type' => $message->getType()
            ];
        }, $messages);
    }
}
