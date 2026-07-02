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

use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\config;
use Magewirephp\Magewire\ComponentHook;
class SupportMultipleRootElementDetection extends ComponentHook
{
    // Redeclared verbatim from upstream so this file's `config`/`on` function imports
    // land in the generated output — the upstream source calls the bare Laravel global
    // config(), which resolves to the wrong namespace once ported.
    static function provide()
    {
        on('mount', function ($component) {
            if (!config('app.debug')) {
                return;
            }
            return function ($html) use ($component) {
                (new static())->warnAgainstMoreThanOneRootElement($component, $html);
            };
        });
    }
    function warnAgainstMoreThanOneRootElement($component, $html)
    {
        $count = $this->getRootElementCount($html);
        if ($count > 1) {
            throw new MultipleRootElementsDetectedException($component);
        }
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
            if (!$isSelfClosing) {
                $depth++;
            }
        }
        return $roots;
    }
    // HTML elements that never hold children and therefore don't open a depth level.
    protected static $voidElements = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
}