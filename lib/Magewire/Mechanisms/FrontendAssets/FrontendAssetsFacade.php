<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Facade;

use Magewirephp\Magewire\Mechanisms\FrontendAssets\FrontendAssets as FrontendAssetsMechanism;

class FrontendAssetsFacade
{
    function __construct(
        private readonly FrontendAssetsMechanism $mechanism
    ) {
        //
    }

    function getMagewireScriptPath()
    {
        return $this->mechanism->returnJavaScriptAsFile();
    }

    function getMagewireScriptAttributes(): array
    {
        return $this->mechanism->getDataByPath('script.html_attributes', []);
    }
}
