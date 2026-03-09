<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\DataCollection;

use InvalidArgumentException;
use Magewirephp\Magewire\Support\DataCollection;
use RuntimeException;

abstract class Filter
{
    private DataCollection|null $collection = null;
    /** @var array<string, callable> $presets */
    private array $presets = [];

    public function with(callable|TypeFilter|string $filter): static
    {
        if (is_string($filter)) {
            $filter = $this->presets[$filter]
                ?? throw new InvalidArgumentException(
                    sprintf('Data array filter preset "%s" does not exist.', $filter)
                );
        }

        if (is_callable($filter)) {
            return $this->byClosure($filter);
        }

        return $this->byType($filter);
    }

    public function byType(TypeFilter $filter): static
    {
        return $this->byClosure($filter->get());
    }

    public function byAnyType(TypeFilter ...$filters): static
    {
        return $this->byClosure(TypeFilter::any(...$filters));
    }

    public function byOnlyType(TypeFilter ...$filters): static
    {
        return $this->byClosure(TypeFilter::only(...$filters));
    }

    public function byClosure(callable $callable): static
    {
        return $this->chain(array_filter($this->items(), $callable, ARRAY_FILTER_USE_BOTH));
    }

    public function byOffset(int $offset, int $start = 0): static
    {
        return $this->chain(array_slice($this->items(), $start, $offset, true));
    }

    public function byPage(int $page, int $limit = 10): static
    {
        return $this->byOffset($limit, ($page - 1) * $limit);
    }

    public function byKeys(array $keys): static
    {
        return $this->chain(array_intersect_key($this->items(), array_flip($keys)));
    }

    public function byValues(array $values): static
    {
        return $this->chain(array_intersect($this->items(), $values));
    }

    public function byKeyPattern(string $pattern): static
    {
        return $this->chain(array_filter($this->items(), static function ($value, $key) use ($pattern) {
            return preg_match($pattern, (string) $key);
        }, ARRAY_FILTER_USE_BOTH));
    }

    public function byValuePattern(string $pattern): static
    {
        return $this->chain(array_filter($this->items(), static function ($value) use ($pattern) {
            return preg_match($pattern, (string) $value);
        }));
    }

    public function byRange($min, $max): static
    {
        return $this->chain(array_filter($this->items(), static function ($value) use ($min, $max) {
            return $value >= $min && $value <= $max;
        }));
    }

    public function byInstance(string $type): static
    {
        return $this->chain(array_filter($this->items(), static function ($value) use ($type) {
            return $value instanceof $type;
        }));
    }

    public function byUnique(): static
    {
        return $this->chain(array_unique($this->items(), SORT_REGULAR));
    }

    public function byEmpty(): static
    {
        return $this->chain(array_filter($this->items(), static fn ($value) => empty($value)));
    }

    public function byNotEmpty(): static
    {
        return $this->chain(array_filter($this->items(), static fn ($value) => ! empty($value)));
    }

    public function byJsonEncodable(): static
    {
        return $this->byType(TypeFilter::JSON_ENCODABLE);
    }

    public function using(DataCollection $collection): static
    {
        $this->collection = $collection;
        return $this;
    }

    public function preset(string $name, callable $filter): static
    {
        $this->presets[$name] = $filter;
        return $this;
    }

    public function shorten(callable $callable): array
    {
        return $this->byClosure($callable)->return()->all();
    }

    /**
     * Chain method to return to the collection output.
     *
     * @alias return
     */
    public function and(): DataCollection
    {
        return $this->collection();
    }

    public function return(): DataCollection
    {
        return $this->and();
    }

    protected function items(): array
    {
        return $this->collection()->all();
    }

    protected function collection(): DataCollection
    {
        if ($this->collection) {
            return $this->collection;
        }

        throw new RuntimeException('No data collection found.');
    }

    protected function chain(array $result): static
    {
        return $this->collection()->newInstance([
            'items'  => $result,
            'filter' => $this
        ])->filter();
    }
}
