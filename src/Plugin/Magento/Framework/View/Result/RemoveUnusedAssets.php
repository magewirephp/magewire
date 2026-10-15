<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Plugin\Magento\Framework\View\Result;

use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Layout as LayoutResult;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ResolveComponentsViewModel;

/**
 * Removes what Magewire renders on every page from a page that has no Magewire component, as Livewire only injects its
 * assets once a component rendered: the styles in the head and the scripts at the start of the body. Both render before
 * the page content, so before it is known whether a component renders. The root block at the end of the body leaves
 * itself out (root.phtml).
 *
 * Runs on the finished page, before the full page cache stores it, like Magento's own asyncCssLoad plugin.
 */
class RemoveUnusedAssets
{
    /** Inert markers around the output of the "magewire.priority" block. */
    public const PRIORITY_START = '<script type="text/x-magewire" data-magewire-priority="start"></script>';
    public const PRIORITY_END = '<script type="text/x-magewire" data-magewire-priority="end"></script>';

    private const STYLES = '#<link\b[^>]*\bhref="[^"]*/Magewirephp_Magewire/css/magewire(?:\.min)?\.css(?:\?[^"]*)?"[^>]*>\s*#i';

    public function __construct(
        private readonly ResolveComponentsViewModel $resolveComponents
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRenderResult(LayoutResult $subject, LayoutResult $result, ResponseInterface $response): LayoutResult
    {
        if (! $response instanceof HttpResponse) {
            return $result;
        }

        $content = (string) $response->getContent();

        if (! str_contains($content, self::PRIORITY_START)) {
            return $result;
        }

        $response->setContent($this->isMagewireUsed($subject) ? $this->removeMarkers($content) : $this->removeOutput($content));

        return $result;
    }

    /**
     * Keeps Magewire's early output where Magewire is used, without the markers around it.
     */
    public function removeMarkers(string $content): string
    {
        return $this->replace($this->priorityPattern(), '$1', $content);
    }

    /**
     * Removes Magewire's styles and early output from a page that does not use Magewire.
     */
    public function removeOutput(string $content): string
    {
        return $this->replace([$this->priorityPattern(), self::STYLES], '', $content);
    }

    private function priorityPattern(): string
    {
        return '#' . preg_quote(self::PRIORITY_START, '#') . '(.*?)' . preg_quote(self::PRIORITY_END, '#') . '\s*#s';
    }

    /**
     * A failed replacement, e.g. at the PCRE backtrack limit, keeps the page as it is.
     */
    private function replace(string|array $pattern, string $replacement, string $content): string
    {
        return preg_replace($pattern, $replacement, $content) ?? $content;
    }

    private function isMagewireUsed(LayoutResult $result): bool
    {
        if ($this->resolveComponents->doesPageHaveComponents()) {
            return true;
        }

        $root = $result->getLayout()->getBlock('magewire');

        return $root !== false && (bool) $root->getData('render_without_components');
    }
}
