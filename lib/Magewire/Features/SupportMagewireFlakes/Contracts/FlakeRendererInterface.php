<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts;

interface FlakeRendererInterface
{
    /**
     * Render one isolated Flake occurrence.
     *
     * @param FlakeRenderContextInterface $context
     */
    public function render(FlakeRenderContextInterface $context): string;
}
