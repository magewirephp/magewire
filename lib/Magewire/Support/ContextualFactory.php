<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use InvalidArgumentException;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Support\Factory\ResolutionMode;
use ReflectionObject;
use ReflectionProperty;
use UnexpectedValueException;

/**
 * Experimental property-first lookup of object dependencies, with Magento DI fallback.
 *
 * This prototype does not capture constructor arguments or write resolved values back.
 *
 * @mago-expect lint:cyclomatic-complexity
 */
class ContextualFactory
{
    private readonly string $consumerType;

    public function __construct(
        private readonly object $consumer,
        private readonly ObjectManagerInterface $objectManager,
        private readonly ConfigInterface $config,
        string|null $consumerType = null
    ) {
        $this->consumerType = $consumerType ?? Utils::getReflectionWithoutInterceptors($consumer)->getName();
    }

    /**
     * Return the stored dependency, configured argument, or the requested type's shared instance.
     *
     * The name addresses both the property and the constructor argument. An initialized
     * null is intentional; a missing or uninitialized property permits DI fallback.
     *
     * @template T of object
     * @param class-string<T> $type
     * @return T|null
     */
    public function dependency(string $name, string $type): object|null
    {
        return $this->resolve($name, $type, ResolutionMode::Configured);
    }

    /**
     * Reuse a stored value or get the selected DI type's shared instance.
     *
     * @template T of object
     * @param class-string<T> $type
     * @return T|null
     */
    public function get(string $name, string $type): object|null
    {
        return $this->resolve($name, $type, ResolutionMode::Shared);
    }

    /**
     * Create a fresh instance of the selected DI type, bypassing stored properties.
     *
     * Literal object/null bindings have no reconstructible DI type and are rejected.
     *
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    public function create(string $name, string $type, array $arguments = []): object
    {
        return (
            $this->resolve($name, $type, ResolutionMode::Fresh, $arguments) ?? throw new UnexpectedValueException(sprintf(
                'Dependency [%s::$%s] cannot be created as null.',
                $this->consumerType,
                $name
            ))
        );
    }

    private function resolve(string $name, string $type, ResolutionMode $mode, array $parameters = []): object|null
    {
        if ($name === '' || $type === '') {
            throw new InvalidArgumentException('A dependency name and fallback type are required.');
        }

        if ($mode !== ResolutionMode::Fresh) {
            $property = $this->findProperty($name);

            if ($property !== null && ! $property->isStatic() && $property->isInitialized($this->consumer)) {
                return $this->checkType($property->getValue($this->consumer), $name, $type);
            }
        }

        $arguments = $this->config->getArguments($this->consumerType) ?? [];

        if (array_key_exists($name, $arguments)) {
            return $this->checkType($this->resolveArgument($arguments[$name], $name, $mode, $parameters), $name, $type);
        }

        return $this->checkType($this->resolveType($type, $mode, $parameters, true), $name, $type);
    }

    private function findProperty(string $name): ReflectionProperty|null
    {
        for ($class = new ReflectionObject($this->consumer); $class !== false; $class = $class->getParentClass()) {
            if ($class->hasProperty($name)) {
                return $class->getProperty($name);
            }
        }

        return null;
    }

    private function resolveArgument(mixed $argument, string $name, ResolutionMode $mode, array $parameters): mixed
    {
        if ($argument === null || is_object($argument)) {
            return $this->resolveLiteral($argument, $name, $mode);
        }

        if (is_array($argument)) {
            // Developer-mode DI uses instance/shared; compiled DI encodes the lifecycle in the key.
            if (array_key_exists('instance', $argument)) {
                return $this->resolveType($argument['instance'], $mode, $parameters, $argument['shared'] ?? null);
            }

            if (array_key_exists('_i_', $argument)) {
                return $this->resolveType($argument['_i_'], $mode, $parameters, true);
            }

            if (array_key_exists('_ins_', $argument)) {
                return $this->resolveType($argument['_ins_'], $mode, $parameters, false);
            }

            if (array_key_exists('_vn_', $argument)) {
                return $this->resolveLiteral(null, $name, $mode);
            }

            if (array_key_exists('_v_', $argument)) {
                return $this->resolveLiteral($argument['_v_'], $name, $mode);
            }
        }

        throw new UnexpectedValueException(sprintf('Dependency [%s::$%s] is not configured as an object or null.', $this->consumerType, $name));
    }

    private function resolveType(string $type, ResolutionMode $mode, array $parameters, bool|null $shared): mixed
    {
        $useShared = match ($mode) {
            ResolutionMode::Shared => true,
            ResolutionMode::Fresh => false,
            ResolutionMode::Configured => $shared ?? $this->config->isShared($type)
        };

        return $useShared ? $this->objectManager->get($type) : $this->objectManager->create($type, $parameters);
    }

    private function resolveLiteral(mixed $value, string $name, ResolutionMode $mode): mixed
    {
        if ($mode === ResolutionMode::Fresh) {
            throw new UnexpectedValueException(sprintf('Dependency [%s::$%s] has no configured DI type name to create.', $this->consumerType, $name));
        }

        return $value;
    }

    private function checkType(mixed $value, string $name, string $type): object|null
    {
        if ($value !== null && ! $value instanceof $type) {
            throw new UnexpectedValueException(sprintf('Dependency [%s::$%s] must implement or extend [%s]; [%s] given.', $this->consumerType, $name, $type, get_debug_type($value)));
        }

        return $value;
    }
}
