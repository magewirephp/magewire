<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace;

use InvalidArgumentException;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Contracts\FlakeNamespaceInterface;

class FlakeNamespacePool
{
    /** @var array<string, FlakeNamespaceInterface> */
    private array $namespaces = [];

    /**
     * @param array<array-key, FlakeNamespaceInterface> $namespaces
     */
    public function __construct(array $namespaces = [])
    {
        foreach ($namespaces as $namespace) {
            if (! $namespace instanceof FlakeNamespaceInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Flake namespace entries must implement %s; received %s.',
                    FlakeNamespaceInterface::class,
                    get_debug_type($namespace)
                ));
            }

            $prefix = $namespace->prefix();

            if (isset($this->namespaces[$prefix])) {
                throw new InvalidArgumentException(sprintf('Duplicate Flake namespace prefix "%s".', $prefix));
            }

            $this->namespaces[$prefix] = $namespace;
        }
    }

    /**
     * Determine whether a namespace is registered.
     *
     * @param string $prefix
     */
    public function has(string $prefix): bool
    {
        return isset($this->namespaces[$prefix]);
    }

    /**
     * Return a registered namespace.
     *
     * @param string $prefix
     * @throws InvalidArgumentException When the prefix is not registered.
     */
    public function get(string $prefix): FlakeNamespaceInterface
    {
        return $this->namespaces[$prefix] ?? throw new InvalidArgumentException(sprintf(
            'Unknown Flake namespace prefix "%s".',
            $prefix
        ));
    }

    /**
     * Return all namespaces by exact prefix.
     *
     * @return array<string, FlakeNamespaceInterface>
     */
    public function all(): array
    {
        return $this->namespaces;
    }
}
