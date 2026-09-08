<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts;

use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakeAncestorFrames;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakeAttributeBag;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakePropBag;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakeSlotBag;

interface FlakeRenderContextInterface
{
    /**
     * Return the occurrence identifier.
     */
    public function id(): string;

    /**
     * Return the namespace selected for this occurrence.
     */
    public function namespace(): FlakeNamespaceInterface;

    /**
     * Return the immutable component definition.
     */
    public function definition(): FlakeDefinitionInterface;

    /**
     * Return occurrence-local block data.
     *
     * @return array<string, mixed>
     */
    public function data(): array;

    /**
     * Return occurrence-local properties.
     */
    public function props(): FlakePropBag;

    /**
     * Return occurrence-local HTML attributes.
     */
    public function attributes(): FlakeAttributeBag;

    /**
     * Return occurrence-local slots.
     */
    public function slots(): FlakeSlotBag;

    /**
     * Return the ancestor frames visible to this occurrence.
     */
    public function ancestors(): FlakeAncestorFrames;
}
