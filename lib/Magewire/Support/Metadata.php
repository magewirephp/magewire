<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

class Metadata
{
    // Fabricates (Lazy Initialization pattern).
    protected DataArray|null $data = null;

    public function __construct(
        protected DataArrayFactory $dataArrayFactory
    ) {
        //
    }

    public function increment(string $prop, int $by = 1): static
    {
        $subject = $this->data()->get($prop, 0);

        if (is_int($subject)) {
            $this->data()->set($prop, ($subject + $by));
        }

        return $this;
    }

    public function decrement(string $prop, int $by = 1): static
    {
        $subject = $this->data()->get($prop, 0);

        if (is_int($subject) && $subject > 0) {
            $this->data()->set($prop, ($subject - $by));
        }

        return $this;
    }

    public function push(string $prop, mixed $value, string|null $key = null): static
    {
        $key ??= Random::integer();

        if (is_array($this->get($prop))) {
            $this->data()->set($prop, array_merge($this->get($prop), [$key => $value]));
        }

        return $this;
    }

    public function get(string $prop): mixed
    {
        return $this->data()->get($prop);
    }

    public function fetch(callable $filter): array
    {
        return $this->data()->fetch($filter);
    }

    protected function data(): DataArray
    {
        return $this->data ??= $this->dataArrayFactory->create();
    }
}
