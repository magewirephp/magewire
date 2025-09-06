<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportNestingComponents;

use Magewirephp\Magewire\Drawer\Utils;
use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\trigger;
use function Magewirephp\Magewire\config;

class SupportNestingComponents extends \Livewire\Features\SupportNestingComponents\SupportNestingComponents
{
    static function provide()
    {
        on('pre-mount', function ($name, $params, $key, $parent, $hijack) {
            // If this has already been rendered spoof it...
            if ($parent && static::hasPreviouslyRenderedChild($parent, $key)) {
                [$tag, $childId] = static::getPreviouslyRenderedChild($parent, $key);

                $finish = trigger('mount.stub', $tag, $childId, $params, $parent, $key);

                $html = "<{$tag} wire:id=\"{$childId}\"></{$tag}>";

                static::setParentChild($parent, $key, $tag, $childId);

                $hijack($finish($html));
            }
        });

        on('mount', function ($component, $params, $key, $parent) {
            $start = null;
            if ($parent && config('app.debug')) {
                $start = microtime(true);
            }

            static::setParametersToMatchingProperties($component, $params);

            return function ($html) use ($component, $key, $parent, $start) {
                if ($parent) {
                    if (config('app.debug')) {
                        trigger('profile', 'child:'.$component->getId(), $parent->getId(), [$start, microtime(true)]);
                    }

                    preg_match('/<([a-zA-Z0-9\-]*)/', $html, $matches, PREG_OFFSET_CAPTURE);
                    $tag = $matches[1][0];
                    static::setParentChild($parent, $key, $tag, $component->getId());
                }
            };
        });
    }

    static function setParametersToMatchingProperties($component, $params)
    {
        $componentProperties = Utils::getPublicPropertiesDefinedOnSubclass($component);

        foreach ($params as $property => $value) {
            if (array_key_exists($property, $componentProperties)) {
                $component->{$property} = $value; // Assign public component properties that have matching parameters.
            }
        }
    }
}
