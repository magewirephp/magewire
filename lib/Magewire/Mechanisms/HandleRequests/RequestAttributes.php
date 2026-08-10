<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests;

/**
 * Request scoped key value store, allowing request filters to communicate without depending
 * on each other directly. A filter writes its outcome, later filters read it when present.
 *
 * @api
 */
class RequestAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private array $attributes = []
    ) {
    }

    public function set(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function unset(string $key): self
    {
        unset($this->attributes[$key]);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->attributes;
    }
}
