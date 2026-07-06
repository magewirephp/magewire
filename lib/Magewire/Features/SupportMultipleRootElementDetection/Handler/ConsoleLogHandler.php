<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Handler;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Api\MultipleRootElementDetectionHandlerInterface;
use Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\MultipleRootElementsDetectedException;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutManager;

/**
 * Non-fatal behavior: let the page render and surface the violation as a browser console
 * error. The <script> is produced by a block defined in the dedicated layout handle
 * (magewire_multiple_root_element_detection.xml) and loaded off-page through Magewire's
 * dynamic (pageless) layout loader — the same mechanism Flakes use. The template renders
 * the tag via SecureHtmlRenderer, so it carries the CSP nonce/hash and is not blocked by a
 * Content Security Policy. Detection strips <script> blocks before counting and appends
 * after the count, so this cannot re-trigger itself.
 */
class ConsoleLogHandler implements MultipleRootElementDetectionHandlerInterface
{
    private const HANDLE = 'magewire_multiple_root_element_detection';
    private const BLOCK = 'magewire.multiple_root_element_detection.console';

    private LayoutInterface|null $layout = null;

    public function __construct(
        private readonly LayoutManager $layoutManager
    ) {
    }

    public function handle(object $component, string $html, int $rootCount): string
    {
        $block = $this->layout()->getBlock(self::BLOCK);

        if (! $block instanceof AbstractBlock) {
            return $html;
        }

        $block->setData('message', MultipleRootElementsDetectedException::buildMessage($component));

        return $html . $block->toHtml();
    }

    /**
     * Lazily builds a decorated, page-less layout with only the console handle merged in,
     * so the block can be fetched by name without a full page render.
     */
    private function layout(): LayoutInterface
    {
        if ($this->layout === null) {
            $this->layout = $this->layoutManager->decorator()
                ->decorateForPagelessBlockFetching($this->layoutManager->factory()->create());

            $this->layout->getUpdate()->addHandle(self::HANDLE);
        }

        return $this->layout;
    }
}
