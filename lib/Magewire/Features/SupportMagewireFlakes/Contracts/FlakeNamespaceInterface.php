<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts;

interface FlakeNamespaceInterface
{
    /**
     * Return the tag prefix used by this namespace.
     */
    public function prefix(): string;

    /**
     * Return the layout handles containing this namespace's definitions.
     *
     * @return list<string>
     */
    public function handles(): array;

    /**
     * Return the layout container that owns this namespace's definitions.
     */
    public function registryRoot(): string;
}
