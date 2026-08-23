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

class FlakeNamespace implements FlakeNamespaceInterface
{
    private readonly string $prefix;

    /** @var list<string> */
    private readonly array $handles;

    private readonly string $registryRoot;

    /**
     * @param string $prefix
     * @param string|list<string> $handles
     * @param string $registryRoot
     */
    public function __construct(string $prefix, string|array $handles, string $registryRoot)
    {
        if (preg_match('/^[a-z][a-z0-9-]*$/', $prefix) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid Flake namespace prefix "%s".', $prefix));
        }

        $handles = is_string($handles) ? [$handles] : array_values($handles);

        $invalidHandles = array_filter(
            $handles,
            static fn (mixed $handle): bool => ! is_string($handle) || $handle === ''
        );

        if ($handles === [] || $invalidHandles !== []) {
            throw new InvalidArgumentException('A Flake namespace requires one or more layout handles.');
        }

        if ($registryRoot === '') {
            throw new InvalidArgumentException('A Flake namespace requires a registry root.');
        }

        $this->prefix = $prefix;
        $this->handles = $handles;
        $this->registryRoot = $registryRoot;
    }

    /**
     * Return the tag prefix used by this namespace.
     */
    public function prefix(): string
    {
        return $this->prefix;
    }

    /**
     * Return the layout handles containing this namespace's definitions.
     *
     * @return list<string>
     */
    public function handles(): array
    {
        return $this->handles;
    }

    /**
     * Return the layout container that owns this namespace's definitions.
     */
    public function registryRoot(): string
    {
        return $this->registryRoot;
    }
}
