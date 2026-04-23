<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * @deprecated Proof of concept — internal use within Magewire core only.
 *             Not ready for public use. API may change without notice.
 */
class AttributesReader
{
    private ReflectionClass $reflection;
    private ReflectionClass|ReflectionProperty $target;

    /**
     * @throws ReflectionException
     */
    private function __construct(object|string $subject)
    {
        $this->reflection = $subject instanceof ReflectionClass ? $subject : new ReflectionClass($subject);

        $this->target = $this->reflection;
    }

    /**
     * @throws ReflectionException
     */
    public static function for(object|string $subject): static
    {
        return new static($subject);
    }

    /**
     * Scope subsequent calls to a specific property.
     *
     * @throws ReflectionException
     */
    public function property(string $name): static
    {
        $clone = clone $this;
        $clone->target = $this->reflection->getProperty($name);

        return $clone;
    }

    /**
     * Returns the first instantiated attribute of $attributeClass, or null.
     *
     * @template T
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    public function first(string $attributeClass): object|null
    {
        $attrs = $this->target->getAttributes($attributeClass);

        return ! empty($attrs) ? $attrs[0]->newInstance() : null;
    }

    /**
     * Returns all instantiated attributes of $attributeClass.
     *
     * @template T
     * @param class-string<T> $attributeClass
     * @return T[]
     */
    public function all(string $attributeClass): array
    {
        return array_map(static fn (ReflectionAttribute $attr) => $attr->newInstance(), $this->target->getAttributes($attributeClass));
    }

    public function has(string $attributeClass): bool
    {
        return ! empty($this->target->getAttributes($attributeClass));
    }

    /**
     * Returns a map of property name => first attribute instance for all properties bearing $attributeClass.
     *
     * @template T
     * @param class-string<T> $attributeClass
     * @return array<string, T>
     */
    public function properties(string $attributeClass): array
    {
        $result = [];

        foreach ($this->reflection->getProperties() as $property) {
            $attrs = $property->getAttributes($attributeClass);

            if (! empty($attrs)) {
                $result[$property->getName()] = $attrs[0]->newInstance();
            }
        }

        return $result;
    }
}
