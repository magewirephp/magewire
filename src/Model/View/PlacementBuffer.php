<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Stringable;
use Traversable;

/**
 * @implements IteratorAggregate<int, PlacementEntry>
 */
class PlacementBuffer implements Countable, IteratorAggregate, Stringable
{
    /** @var array<int, PlacementEntry> */
    private array $entries = [];

    public function __construct(
        private readonly string $scope,
        private readonly string $name
    ) {
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function add(PlacementEntry $entry): static
    {
        $this->entries[] = $entry;

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * @return array<int, PlacementEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    public function __toString(): string
    {
        return implode('', array_map(static fn (PlacementEntry $entry): string => (string) $entry, $this->all()));
    }
}
