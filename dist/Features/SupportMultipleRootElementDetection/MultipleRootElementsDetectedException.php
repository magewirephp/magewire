<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection;

use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Exceptions\BypassViewHandler;
class MultipleRootElementsDetectedException extends \Exception
{
    use BypassViewHandler;
    function __construct($component)
    {
        parent::__construct(static::buildMessage($component));
    }
    /**
     * Builds the violation message. Extracted so alternative behaviors (console, log)
     * can reuse the exact same wording without throwing.
     */
    public static function buildMessage($component): string
    {
        $name = method_exists($component, 'getName') ? $component->getName() : 'unknown';
        $template = static::resolveTemplate($component);
        $location = $template ? " (template: {$template})" : '';
        return "Magewire only supports a single root element per component. Multiple root elements were detected for component [{$name}]{$location}. Wrap the component markup in a single parent element.";
    }
    /**
     * Best-effort lookup of the block template so the developer can jump straight to
     * the offending view. Returns null when the component has no template-based block.
     */
    private static function resolveTemplate($component)
    {
        if (!method_exists($component, 'magewireBlock')) {
            return null;
        }
        $block = $component->magewireBlock();
        if (!$block instanceof Template) {
            return null;
        }
        try {
            return $block->getTemplateFile() ?: $block->getTemplate();
        } catch (\Throwable $exception) {
            return $block->getTemplate();
        }
    }
}