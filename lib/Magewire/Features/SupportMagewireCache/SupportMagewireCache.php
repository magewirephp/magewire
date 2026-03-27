<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCache;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Drawer\Utils;
use function Magewirephp\Magewire\on;

/**
 * Reads the `wire-cache` layout XML argument from the block and renders
 * it as a `data-mw-cache` HTML attribute on the component root element.
 *
 * Layout XML usage:
 *
 *   <argument name="wire-cache" xsi:type="string">my-cache-key</argument>
 *   <argument name="wire-cache" xsi:type="boolean">true</argument>
 */
class SupportMagewireCache extends ComponentHook
{
    public function provide(): void
    {
        on('render', function (Component $component, AbstractBlock $block) {
            return function (string $html) use ($component, $block) {
                $cacheKey = $block->getData('wire-cache');

                if ($cacheKey === null) {
                    return $html;
                }

                // Boolean true or "1" → fall back to the block name as cache key.
                if ($cacheKey === true || $cacheKey === '1' || $cacheKey === 1) {
                    $cacheKey = $block->getNameInLayout();
                }

                return Utils::insertAttributesIntoHtmlRoot($html, [
                    'data-wire-cache' => (string) $cacheKey,
                ]);
            };
        });
    }
}
