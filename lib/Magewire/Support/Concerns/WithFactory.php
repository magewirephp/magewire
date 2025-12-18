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
    public function newInstance(array $arguments = [], string|null $type = null)
    {
        if ($type && ! is_a($type, static::class, true)) {
            throw new InvalidArgumentException(
                sprintf('Class "%s" must be an instance of or extend "%s".', $type, static::class)
            );
        }

        return $this->newTypeInstance($type ?? static::class, $arguments);
    }

    /**
     * @template T
     * @param class-string<T> $type
     * @return T
     */
    public function newTypeInstance(string $type, array $arguments = [])
    {
        if (! class_exists($type)) {
            throw new InvalidArgumentException(sprintf('Class %s does not exist', $type));
        }
        if (str_ends_with($type, 'Factory')) {
            $type = substr($type, 0, -strlen('Factory'));
        }

        $factory = Factory::get(trim($type) . 'Factory');
        return $factory->create($arguments);
    }
}
