<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use InvalidArgumentException;
use Magewirephp\Magewire\Model\View\FragmentFactory;
use Magewirephp\Magewire\Model\View\PlacementRegistry;
use Magewirephp\Magewire\Model\View\UtilsInterface;

class Fragment implements UtilsInterface
{
    private const PLACEMENT_SCOPE_SCRIPT = 'script';

    public function __construct(
        private readonly FragmentFactory $fragmentFactory,
        private readonly PlacementRegistry $placementRegistry
    ) {
    }

    /**
     * Creates a fragment instance by type name or class name.
     *
     * Supports two lookup methods:
     * 1. Named types registered in $this->types (e.g., 'header', 'footer')
     * 2. Direct class names
     *
     * @param string|null $name
     * @return \Magewirephp\Magewire\Model\View\Fragment|FragmentFactory
     * @throws InvalidArgumentException
     *
     * @see FragmentFactory::$types For registering custom fragment types via DI
     */
    public function make(string|null $name = null): FragmentFactory|\Magewirephp\Magewire\Model\View\Fragment
    {
        if ($name) {
            return $this->fragmentFactory->custom($name);
        }

        return $this->fragmentFactory;
    }

    public function has(string $name): bool
    {
        return $this->placementRegistry->has(self::PLACEMENT_SCOPE_SCRIPT, $name);
    }

    public function render(string $name): string
    {
        return $this->script($name);
    }

    public function container(string $name): string
    {
        return $this->script($name);
    }

    public function script(string $name): string
    {
        return $this->placementRegistry->render(self::PLACEMENT_SCOPE_SCRIPT, $name);
    }
}
