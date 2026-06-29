<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Plugin\Magento\Framework\App\Response;

use Magento\Framework\App\Response\Http as Subject;
use Magewirephp\Magewire\Model\View\PlacementRegistry;

class Http
{
    public function __construct(
        private readonly PlacementRegistry $placementRegistry
    ) {
    }

    public function beforeSendResponse(Subject $subject): void
    {
        $content = $subject->getContent();

        if (is_string($content) && $content !== '') {
            $subject->setContent($this->placementRegistry->resolve($content));
        }
    }
}
