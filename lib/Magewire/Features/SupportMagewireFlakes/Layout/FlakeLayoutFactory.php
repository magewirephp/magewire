<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Layout;

use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeNamespaceInterface;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutManager;

class FlakeLayoutFactory
{
    /**
     * @param LayoutManager $layoutManager
     */
    public function __construct(
        private readonly LayoutManager $layoutManager
    ) {
    }

    /**
     * Create a fresh pageless layout for a namespace.
     *
     * @param FlakeNamespaceInterface $namespace
     */
    public function create(FlakeNamespaceInterface $namespace): LayoutInterface
    {
        $layout = $this->layoutManager->factory()->create();
        $layout = $this->layoutManager->decorator()->decorateForPagelessBlockFetching($layout);
        $layout->getUpdate()->addHandle($namespace->handles());

        return $layout;
    }
}
