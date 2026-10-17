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
        if ($name === '' || $type === '') {
            throw new InvalidArgumentException('A dependency name and fallback type are required.');
        }

        $property = $this->findProperty($name);

        if ($property !== null && ! $property->isStatic() && $property->isInitialized($this->consumer)) {
            return $this->checkType($property->getValue($this->consumer), $name, $type);
        }

        $arguments = $this->config->getArguments($this->consumerType) ?? [];

        if (array_key_exists($name, $arguments)) {
            return $this->checkType($this->resolveArgument($arguments[$name], $name), $name, $type);
        }

        return $this->checkType($this->objectManager->get($type), $name, $type);
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

    private function resolveArgument(mixed $argument, string $name): mixed
    {
        if ($argument === null || is_object($argument)) {
            return $argument;
        }

        if (is_array($argument)) {
            // Developer-mode DI uses instance/shared; compiled DI encodes the lifecycle in the key.
            if (array_key_exists('instance', $argument)) {
                $shared = $argument['shared'] ?? $this->config->isShared($argument['instance']);

                return $shared ? $this->objectManager->get($argument['instance']) : $this->objectManager->create($argument['instance']);
            }

            if (array_key_exists('_i_', $argument)) {
                return $this->objectManager->get($argument['_i_']);
            }

            if (array_key_exists('_ins_', $argument)) {
                return $this->objectManager->create($argument['_ins_']);
            }

            if (array_key_exists('_vn_', $argument)) {
                return null;
            }

            if (array_key_exists('_v_', $argument)) {
                return $argument['_v_'];
            }
        }

        throw new UnexpectedValueException(sprintf('Dependency [%s::$%s] is not configured as an object or null.', $this->consumerType, $name));
    }

    private function checkType(mixed $value, string $name, string $type): object|null
    {
        if ($value !== null && ! $value instanceof $type) {
            throw new UnexpectedValueException(sprintf('Dependency [%s::$%s] must implement or extend [%s]; [%s] given.', $this->consumerType, $name, $type, get_debug_type($value)));
        }

        return $value;
    }
}
