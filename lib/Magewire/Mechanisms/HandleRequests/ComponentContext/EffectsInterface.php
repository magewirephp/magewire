<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentContext;

interface EffectsInterface
{
    /**
     * @param string $html
     * @return $this
     */
    function setHtml(string $html): self;

    /**
     * @return string
     */
    function getHtml(): string;

    /**
     * @param array $returns
     * @return $this
     */
    function setReturns(array $returns): self;

    /**
     * @return array
     */
    function getReturns(): array;
}
