<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Plugin\Magento\Framework\App\Cache;

use Magento\Framework\App\PageCache\Version as Subject;
use Magento\Framework\App\Request\Http as HttpRequest;

class Version
{
    private HttpRequest $httpRequest;

    /**
     * @param HttpRequest $httpRequest
     */
    public function __construct(
        HttpRequest $httpRequest
    ) {
        $this->httpRequest = $httpRequest;
    }

    public function aroundProcess(Subject $subject, callable $proceed): void
    {
        if ($this->httpRequest->isPost() &&
            $this->httpRequest->getHeader('content-type') === 'application/json' &&
            !empty($this->httpRequest->getContent())
        ) {
            $post = json_decode($this->httpRequest->getContent(), true);

            if (isset($post['serverMemo']['reloadSectionData'])) {
                $reloadSectionData = (bool)($post['serverMemo']['reloadSectionData'] ?? true);
                if (!$reloadSectionData) {
                    return;
                }
            }
        }

        $proceed();
    }
}
