<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Plugin\Magento\Framework\App\PageCache;

use Magento\Framework\App\PageCache\Version as Subject;
use Magewirephp\Magewire\Helper\HttpRequest as HttpRequestHelper;

class Version
{
    private HttpRequestHelper $httpRequestHelper;

    public function __construct(
        HttpRequestHelper $httpRequestHelper
    ) {
        $this->httpRequestHelper = $httpRequestHelper;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundProcess(Subject $subject, callable $proceed): void
    {
        if ($this->httpRequestHelper->isMagewireRequest()) {
            return;
        }

        $proceed();
    }
}
