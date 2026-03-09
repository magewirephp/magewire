<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\Management;

use Exception;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolver;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolverFactory;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolverNotFoundException;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ResolversCache;
use Psr\Log\LoggerInterface;

class ComponentResolverManager
{
    /**
     * @param array<string, ComponentResolver|string|false|null> $resolvers
     */
    public function __construct(
        private LoggerInterface $logger,
        private ResolversCache $resolversCache,
        private ComponentResolverFactory $componentResolverFactory,
        private array $resolvers = []
    ) {
        $this->resolvers = array_filter($this->resolvers, static fn ($resolver) => is_object($resolver) || is_string($resolver));
    }

    /**
     * Locate a matching resolver which complies to the given block.
     *
     * @throws ComponentResolverNotFoundException
     * @throws RuntimeException
     */
    public function resolve(AbstractBlock $block): ComponentResolver
    {
        $cache = $this->resolversCache->fetch();

        // BACKWARDS COMPATIBILITY START: Handling cases where resolver cache values were stored as strings and
        //                                the magewire cache not yet has been flushed.
        $resolver = $cache[$block->getCacheKey()] ?? $cache[$block->getCacheKey()]['accessor'] ?? null;
        // BACKWARDS COMPATIBILITY END.

        /*
         * The resolver is determined through one of the following options:
         *
         * 1. The resolver is manually assigned via a Magewire resolver argument,
         *    specified as a xsi-type string representing a DI-mapped resolver key or full class path.
         * 2. The resolver is automatically filtered and executed by all available
         *    injected resolvers, each calling the `complies()` method.
         */
        $resolver ??= $block->getData('magewire:resolver') ?? null;

        if ($resolver instanceof ComponentResolver) {
            return $resolver;
        }

        // Check the Magewire blocks cache to find the cached resolver accessor.
        $resolver ??= $cache['blocks'][$this->getBlockCacheKey($block)] ?? null;

        if (is_string($resolver)) {
            if ($this->hasResolverClassInMapping($resolver)) {
                try {
                    return $this->createResolverByAccessor($resolver);
                } catch (NotFoundException $exception) {
                    $this->logger->info($exception->getMessage(), ['exception' => $exception]);
                }
            }

            // If the resolver string wasn't found in the class mapping or instantiation failed,
            // attempt to resolve the class dynamically and instantiate it if successful.
            try {
                return $this->createResolverByType($this->getResolverClass($resolver));
            } catch (NotFoundException $exception) {
                $this->logger->info(sprintf('Magewire resolver data value found on block, but "%s" can not be resolved.', $resolver), ['exception' => $exception]);
            }
        }

        try {
            // Last resort: find a resolver that matches the given block.
            // @todo Consider prioritizing resolvers instead of always using the first match.
            $resolver = array_key_first(array_filter($this->resolvers, static fn (ComponentResolver $resolver) => $resolver->complies($block, $block->getData('magewire'))));

            $resolver = $this->createResolverByAccessor($resolver);
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            throw new ComponentResolverNotFoundException(sprintf('No component resolver complies for block %s.', $block->getNameInLayout()));
        }

        if ($resolver->remember()) {
            $this->cache($block, $resolver);
        }

        return $resolver;
    }

    public function resolverFactory(): ComponentResolverFactory
    {
        return $this->componentResolverFactory;
    }

    /**
     * Create a resolver instance by its accessor.
     *
     * @throws ComponentResolverNotFoundException
     */
    public function createResolverByAccessor(string $resolver, array $arguments = []): ComponentResolver
    {
        return $this->createResolverByType($this->getResolverClass($resolver), $arguments);
    }

    /**
     * @throws ComponentResolverNotFoundException
     */
    public function createResolverByType(string $type, array $arguments = []): ComponentResolver
    {
        $instance = $this->resolverFactory()->create($type, $arguments);

        if ($instance instanceof ComponentResolver) {
            return $instance;
        }

        throw new ComponentResolverNotFoundException('Component resolver cannot be found.');
    }

    /**
     * Returns if the given resolver can be found by its accessor.
     */
    public function hasResolverClassInMapping(string $resolver): bool
    {
        try {
            return is_string($this->getResolverClass($resolver));
        } catch (ComponentResolverNotFoundException $exception) {
        }

        return false;
    }

    /**
     * @template T of ComponentResolver
     * @return class-string<T>
     * @throws ComponentResolverNotFoundException
     */
    private function getResolverClass(string $resolver): string
    {
        $cache = $this->resolversCache->fetch();
        $data = $cache['resolvers'][$resolver] ?? null;

        if ($data) {
            return $data['class'];
        }
        if (class_exists($resolver)) {
            return $resolver;
        }

        if (isset($this->resolvers[$resolver])) {
            if (is_object($this->resolvers[$resolver])) {
                return $this->resolvers[$resolver]::class;
            }

            return $this->resolvers[$resolver];
        }

        throw new ComponentResolverNotFoundException(sprintf('No block resolver found for accessor "%s"', $resolver));
    }

    /**
     * @throws RuntimeException
     */
    private function getBlockCacheKey(AbstractBlock $block, bool $unique = true): string
    {
        return $unique ? sprintf('%s_%s', $block->getCacheKey(), $block->getNameInLayout()) : $block->getCacheKey();
    }

    /**
     * @throws RuntimeException
     */
    private function cache(AbstractBlock $block, ComponentResolver $resolver): void
    {
        $cache = $this->resolversCache->fetch();
        $cache['blocks'][$this->getBlockCacheKey($block)] = $resolver->getAccessor();

        $resolvers = $cache['resolvers'] ?? [$resolver->getAccessor() => []];

        $resolvers[$resolver->getAccessor()]['blocks'][] = $this->getBlockCacheKey($block);
        $resolvers[$resolver->getAccessor()]['class'] ??= $resolver::class;
        $resolvers[$resolver->getAccessor()]['name'] ??= $resolver->getAccessor();

        $cache['resolvers'] = $resolvers;

        // Attempt to cache the resolvers, regardless of whether caching is enabled or disabled.
        $this->resolversCache->save($cache);
    }
}
