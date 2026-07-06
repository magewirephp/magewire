<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection;

use Magento\Framework\App\ObjectManager;

use function Magewirephp\Magewire\on;

class SupportMultipleRootElementDetection extends \Livewire\Features\SupportMultipleRootElementDetection\SupportMultipleRootElementDetection
{
    // HTML elements that never hold children and therefore don't open a depth level.
    protected static $voidElements = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    // Redeclared verbatim from upstream so this file's `on` function import lands in the
    // generated output. Unlike upstream, the reaction is no longer gated on debug mode:
    // the configured behavior (see the handler manager) decides, with "off" disabling it.
    static function provide()
    {
        on('mount', function ($component) {
            return function ($html) use ($component) {
                // Return the (possibly modified) HTML so the EventBus finisher forwards it.
                return (new static())->warnAgainstMoreThanOneRootElement($component, $html);
            };
        });
    }

    /**
     * Delegates the reaction to the configured handler. "off" short-circuits before the
     * HTML is even parsed; a single root is always fine; otherwise the handler decides
     * (throw, browser console, log, ...) and returns the HTML to forward.
     */
    function warnAgainstMoreThanOneRootElement($component, $html)
    {
        $manager = ObjectManager::getInstance()->get(MultipleRootElementDetectionHandlerManager::class);

        if (! $manager->isEnabled()) {
            return $html;
        }

        $count = $this->getRootElementCount($html);

        if ($count <= 1) {
            return $html;
        }

        return $manager->handle($component, $html, $count);
    }

    /**
     * Counts top-level (depth 0) element openings without a full DOM parse.
     *
     * Replaces the upstream DOMDocument counter: Magewire renders partial fragments
     * (no <html>/<body> wrapper), so loadHTML() adds parsing overhead and wrapping
     * quirks. Comments and <script> blocks are stripped first so their contents and
     * closing tags don't skew the walk; the remaining tags are tokenized and walked
     * while tracking nesting depth, and every opening tag seen at depth 0 marks a root.
     */
    function getRootElementCount($html)
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;

        preg_match_all('/<(\/?)([a-zA-Z][a-zA-Z0-9\-]*)\b[^>]*?(\/?)>/', $html, $matches, PREG_SET_ORDER);

        $depth = 0;
        $roots = 0;

        foreach ($matches as $match) {
            $isClosing = $match[1] === '/';
            $name = strtolower($match[2]);
            $isSelfClosing = $match[3] === '/' || in_array($name, static::$voidElements, true);

            if ($isClosing) {
                if ($depth > 0) {
                    $depth--;
                }

                continue;
            }

            if ($depth === 0) {
                $roots++;
            }

            if (! $isSelfClosing) {
                $depth++;
            }
        }

        return $roots;
    }
}
