<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\FrontendAssets;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository as AssetsRepository;
use Magewirephp\Magewire\Concerns\AsDataObject;
use function Magewirephp\Magewire\on;

class FrontendAssets extends \Livewire\Mechanisms\FrontendAssets\FrontendAssets
{
    // Trait inclusion to transform the class into a data object.
    use AsDataObject;

    function __construct(
        private readonly AssetsRepository $assetsRepository,
        private readonly RequestInterface $request
    ) {
        //
    }

    function boot()
    {
        $this->setScriptRoute(fn () => $this->returnJavaScriptAsFile());

        on('flush-state', function () {
            $this->hasRenderedScripts = false;
            $this->hasRenderedStyles = false;
        });
    }

    function returnJavaScriptAsFile()
    {
        $url = $this->assetsRepository->getUrlWithParams($this->getDataByPath('script.file_path'), [
            '_secure' => $this->request->isSecure(),
        ]);

        $queryParams = $this->getDataByPath('script.query_params');

        if (is_array($queryParams)) {
            return $url . '?'. http_build_query($queryParams);
        }

        return $url;
    }
}
