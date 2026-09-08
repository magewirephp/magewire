<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts;

use Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition\FlakeDefinitionMetadata;

interface FlakeDefinitionInterface
{
    /**
     * Return the namespace that owns this definition.
     */
    public function namespace(): FlakeNamespaceInterface;

    /**
     * Return the exact component name from layout XML.
     */
    public function name(): string;

    /**
     * Return the layout block class used by the definition.
     *
     * @return class-string
     */
    public function blockClass(): string;

    /**
     * Return the configured PHTML template when available.
     */
    public function template(): string|null;

    /**
     * Return normalized Flakes metadata.
     */
    public function metadata(): FlakeDefinitionMetadata;
}
