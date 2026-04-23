<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireNotifications;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;

class SupportMagewireNotifications extends ComponentHook
{
    function dehydrate(ComponentContext $context): void
    {
        $component = $this->component();

        if ($component && $component->magewireNotifications()->count() > 0) {
            $context->pushEffect('dispatches', [
                'name' => 'magewire:notifications',
                'params' => $this->mapNotifications($component->magewireNotifications())
            ]);
        }
    }

    private function mapNotifications(MagewireNotifications $notifications): array
    {
        return array_map(static function (Notification $message) {
            return [
                'text' => $message->notification()->render(),
                'type' => $message->type()
            ];
        }, $notifications->fetch());
    }
}
