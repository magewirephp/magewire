<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Exceptions\ContainerEntryNotFoundException;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Laravel-style service resolution backed by Magento's object manager.
 *
 * Static application bindings remain owned by Magento DI. Runtime singleton
 * and instance overrides live for the lifetime of this shared object.
 *
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:too-many-methods
 */
class ApplicationContainer implements ContainerInterface, ObjectManagerInterface
{
    /** @var array<string, Closure|string> */
    private array $singletons = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var list<string> */
    private array $resolving = [];

    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly ConfigInterface $config,
        private readonly Containers $containers
    ) {
    }

    /**
     * Resolve a service using runtime overrides, Magewire aliases, and Magento DI.
     *
     * Explicit arguments always cause a fresh contextual build, matching
     * Laravel's handling of parameters passed to Container::make().
     */
    public function make($abstract, array $parameters = []): mixed
    {
        $abstract = $this->normalizeAbstract($abstract);

        if ($parameters === [] && array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        if (array_key_exists($abstract, $this->singletons)) {
            return $this->resolveSingleton($abstract, $parameters);
        }

        return $this->resolveConfigured($abstract, $parameters);
    }

    public function makeWith($abstract, array $parameters = []): mixed
    {
        return $this->make($abstract, $parameters);
    }

    /**
     * PSR container resolution entry point.
     *
     * @throws ContainerEntryNotFoundException
     */
    public function get($id): mixed
    {
        if (! is_string($id) || ! $this->has($id)) {
            $id = is_string($id) ? $id : get_debug_type($id);

            throw new ContainerEntryNotFoundException(sprintf('Target [%s] is not bound in Magewire or Magento DI.', $id));
        }

        return $this->make($id);
    }

    /**
     * Preserve Magento ObjectManager::create() semantics for existing consumers.
     */
    public function create($type, array $arguments = []): mixed
    {
        return $this->objectManager->create($type, $arguments);
    }

    /**
     * Preserve Magento ObjectManager::configure() semantics for existing consumers.
     */
    public function configure(array $configuration): void
    {
        $this->objectManager->configure($configuration);
    }

    public function has(string $id): bool
    {
        $id = ltrim($id, '\\');

        if ($id === '') {
            return false;
        }

        return $this->bound($id) || $this->isDirectMagentoType($id);
    }

    public function bound($abstract): bool
    {
        if (! is_string($abstract)) {
            return false;
        }

        $abstract = ltrim($abstract, '\\');

        if ($abstract === '') {
            return false;
        }

        return array_key_exists($abstract, $this->instances) || array_key_exists($abstract, $this->singletons) || $this->containers->has($abstract) || $this->isConfiguredMagentoType($abstract);
    }

    public function singleton($abstract, $concrete = null): void
    {
        $abstract = $this->normalizeAbstract($abstract);
        $concrete ??= $abstract;

        if (! is_string($concrete) && ! $concrete instanceof Closure) {
            throw new BindingResolutionException(sprintf('Singleton [%s] must resolve to a class name or closure; %s given.', $abstract, get_debug_type($concrete)));
        }

        unset($this->instances[$abstract]);

        $this->singletons[$abstract] = $concrete;
    }

    public function instance($abstract, $instance): mixed
    {
        $abstract = $this->normalizeAbstract($abstract);

        unset($this->singletons[$abstract]);

        return $this->instances[$abstract] = $instance;
    }

    private function resolveSingleton(string $abstract, array $arguments): mixed
    {
        if (in_array($abstract, $this->resolving, true)) {
            throw new BindingResolutionException(sprintf('Circular runtime singleton detected while resolving [%s].', implode(' -> ', [...$this->resolving, $abstract])));
        }

        $this->resolving[] = $abstract;

        try {
            $concrete = $this->singletons[$abstract];
            $resolved = $this->resolveConcrete($abstract, $concrete, $arguments);

            if ($arguments === []) {
                $this->instances[$abstract] = $resolved;
            }

            return $resolved;
        } finally {
            array_pop($this->resolving);
        }
    }

    private function resolveConcrete(string $abstract, Closure|string $concrete, array $arguments): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $arguments);
        }
        if ($concrete === $abstract) {
            return $this->resolveConfigured($concrete, $arguments);
        }

        return $this->make($concrete, $arguments);
    }

    private function resolveConfigured(string $abstract, array $arguments): mixed
    {
        if ($this->isDirectMagentoType($abstract)) {
            return $this->resolveMagentoType($abstract, $arguments);
        }

        if ($this->containers->has($abstract)) {
            if ($arguments === []) {
                return $this->containers->item($abstract);
            }

            return $this->resolveMagentoType($this->containers->itemType($abstract), $arguments);
        }

        if ($this->isConfiguredMagentoType($abstract)) {
            return $this->resolveMagentoType($abstract, $arguments);
        }

        throw new BindingResolutionException(sprintf('Target [%s] is not bound in Magewire or Magento DI.', $abstract));
    }

    private function resolveMagentoType(string $abstract, array $arguments): mixed
    {
        try {
            return $arguments === [] ? $this->objectManager->get($abstract) : $this->objectManager->create($abstract, $arguments);
        } catch (Throwable $exception) {
            throw new BindingResolutionException(sprintf('Unable to resolve [%s] through Magento DI: %s', $abstract, $exception->getMessage()), 0, $exception);
        }
    }

    private function isDirectMagentoType(string $abstract): bool
    {
        if (class_exists($abstract)) {
            return true;
        }

        return interface_exists($abstract) && array_key_exists($abstract, $this->config->getPreferences());
    }

    private function isConfiguredMagentoType(string $abstract): bool
    {
        return array_key_exists($abstract, $this->config->getPreferences()) || array_key_exists($abstract, $this->config->getVirtualTypes());
    }

    private function normalizeAbstract(mixed $abstract): string
    {
        if (! is_string($abstract) || trim($abstract) === '') {
            throw new BindingResolutionException(sprintf('Container abstracts must be non-empty strings; %s given.', get_debug_type($abstract)));
        }

        return ltrim($abstract, '\\');
    }
}
