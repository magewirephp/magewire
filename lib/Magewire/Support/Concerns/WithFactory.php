<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\Concerns;

use InvalidArgumentException;
use Magewirephp\Magewire\Support\Factory;

trait WithFactory
{
    /**
     * Creates a new instance of the current or given class using its factory.
     * When a type is provided, it must be equal to or a subclass of the current class.
     *
     * @template T of static
     * @param class-string<T>|null $type
     * @return T
     */
    public function newInstance(array $arguments = [], string|null $type = null): static
    {
        $type ??= static::class;

        if ($type !== static::class && ! is_subclass_of($type, static::class)) {
            throw new InvalidArgumentException(sprintf('Type %s must be equal to or a subclass of %s', $type, static::class));
        }

        return $this->newTypeInstance($type, $arguments);
    }

    /**
     * Creates a new instance of the current or given class using its factory.
     * When a type is provided, it must be equal to or a subclass of the current class.
     *
     * @template T
     * @param class-string<T> $type
     * @return T
     */
    public function newTypeInstance(string $type, array $arguments = []): mixed
    {
        if (str_ends_with($type, 'Factory')) {
            $type = substr($type, 0, -strlen('Factory'));
        }
        if (! class_exists($type)) {
            throw new InvalidArgumentException(sprintf('Class %s does not exist', $type));
        }

        $factory = Factory::get(trim($type) . 'Factory');
        return $factory->create($arguments);
    }
}
