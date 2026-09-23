<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireLoaders;

use Magento\Framework\Phrase;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\LayoutArgumentOverlay;

use function Magewirephp\Magewire\map_with_keys;

class SupportMagewireLoaders extends ComponentHook
{
    public function __construct(
        private readonly LayoutArgumentOverlay $layoutArgumentOverlay
    ) {
    }

    function dehydrate(ComponentContext $context): void
    {
        $loader = $this->layoutArgumentOverlay->value($context->component, 'loader', $context->component->getLoader(), [null]);

        if ($loader) {
            $context->pushEffect('loader', $this->translateLoader($loader));
        }
    }

    private function translateLoader(mixed $loader): mixed
    {
        if (is_string($loader)) {
            return __($loader);
        }

        if (! is_array($loader)) {
            return $loader;
        }

        return map_with_keys(fn ($value, $key) => [$key => $this->translateMessages($value)], $loader);
    }

    private function translateMessages(mixed $value): mixed
    {
        if (is_string($value) || $value instanceof Phrase) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return $value;
        }

        $messages = [];

        foreach ($value as $key => $message) {
            if ($message instanceof Phrase) {
                $messages[$key] = $message;
                continue;
            }

            if (is_string($message)) {
                $messages[$key] = __($message);
            }
        }

        return $messages;
    }
}
