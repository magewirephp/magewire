<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection;

use Magento\Framework\View\Element\Template;

class MultipleRootElementsDetectedException extends \Livewire\Features\SupportMultipleRootElementDetection\MultipleRootElementsDetectedException
{
    function __construct($component)
    {
        $name = method_exists($component, 'getName') ? $component->getName() : 'unknown';
        $template = static::resolveTemplate($component);
        $location = $template ? " (template: {$template})" : '';

        parent::__construct("Magewire only supports a single root element per component. Multiple root elements were detected for component [{$name}]{$location}. Wrap the component markup in a single parent element.");
    }

    /**
     * Best-effort lookup of the block template so the developer can jump straight to
     * the offending view. Returns null when the component has no template-based block.
     */
    private static function resolveTemplate($component)
    {
        if (! method_exists($component, 'magewireBlock')) {
            return null;
        }

        $block = $component->magewireBlock();

        if (! $block instanceof Template) {
            return null;
        }

        try {
            return $block->getTemplateFile() ?: $block->getTemplate();
        } catch (\Throwable $exception) {
            return $block->getTemplate();
        }
    }
}