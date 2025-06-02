<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\FrontendAssets;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Mechanisms\FrontendAssets\FrontendAssets as FrontendAssetsMechanism;

class FrontendAssetsViewModel implements ArgumentInterface
{
    public function __construct(
        private readonly FrontendAssetsMechanism $frontendAssetsMechanism,
        private readonly Escaper $escaper
    ) {
        //
    }

    function getScriptPath(): string
    {
        return $this->frontendAssetsMechanism->returnJavaScriptAsFile();
    }

    function getScriptAttributes(): string
    {
        $attributes = $this->frontendAssetsMechanism->getDataByPath('script.html_attributes', []);

        return implode(' ', array_map(function ($attribute, ?string $expression) {
            return $expression === null ? $attribute : sprintf('%s="%s"', $attribute, $this->escaper->escapeHtmlAttr($expression));
        }, array_keys($attributes), array_values($attributes)));
    }
}
