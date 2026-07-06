<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Api;

/**
 * Strategy for reacting to a component that rendered more than one root element.
 *
 * Implementations decide what happens: throw, warn in the browser, log, or nothing.
 * Register an implementation under a key in the handler pool (see
 * MultipleRootElementDetectionHandlerManager) and expose that key as a behavior option.
 *
 * @api
 */
interface MultipleRootElementDetectionHandlerInterface
{
    /**
     * React to the detected violation and return the HTML to forward down the pipeline.
     *
     * The returned string replaces the component HTML (see EventBus finisher), so a
     * handler may append markup (e.g. an inline console script). Throwing is allowed and
     * aborts the render.
     */
    public function handle(object $component, string $html, int $rootCount): string;
}
