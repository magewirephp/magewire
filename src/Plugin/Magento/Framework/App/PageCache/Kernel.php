<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Plugin\Magento\Framework\App\PageCache;

use Magento\Framework\App\PageCache\Kernel as Subject;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magewirephp\Magewire\Model\View\PlacementRegistry;

class Kernel
{
    public function __construct(
        private readonly PlacementRegistry $placementRegistry
    ) {
    }

    public function beforeProcess(Subject $subject, ResponseHttp $response): array
    {
        $content = $response->getContent();

        if (is_string($content) && $content !== '') {
            $response->setContent($this->placementRegistry->resolve($content));
        }

        return [$response];
    }
}
