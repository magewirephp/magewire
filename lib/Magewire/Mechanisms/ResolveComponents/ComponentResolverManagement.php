<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magento\App\Cache\Type\Magewire as MagewireCache;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolver;
use Psr\Log\LoggerInterface;

class ComponentResolverManagement
{
    /**
     * @param array<string, ComponentResolver|string|false|null> $resolvers
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MagewireCache $magewireCache,
        private array $resolvers = []
    ) {
        $this->resolvers = array_filter($this->resolvers, fn ($resolver) => is_object($resolver) || is_string($resolver));
    }

    /**
     * Locate a matching resolver which complies to the given block.
     *
     * @throws ComponentResolverNotFoundException
     * @throws RuntimeException
     */
    public function resolve(AbstractBlock $block): ComponentResolver
    {
        $cache = $this->magewireCache->resolvers()->fetch();

        // BACKWARDS COMPATIBILITY START: Handling cases where resolver cache values were stored as strings and
        //                                the magewire cache not yet has been flushed.
        $resolver = $cache[$block->getCacheKey()] ?? $cache[$block->getCacheKey()]['accessor'] ?? null;
        // BACKWARDS COMPATIBILITY END.

        /*
         * The resolver is determined through one of the following options:
         *
         * 1. The resolver is manually assigned via a Magewire resolver argument,
         *    specified as a xsi-type string representing a DI-mapped resolver key.
         * 2. The resolver is automatically filtered and executed by all available
         *    injected resolvers, each calling the `complies()` method.
         */
        $resolver ??= $block->getData('magewire:resolver') ?? null;

        // Check the Magewire blocks cache to find the cached resolver accessor.
        $resolver ??= $cache['blocks'][$this->getBlockCacheKey($block)] ?? null;

        if (is_string($resolver) && $this->hasResolverClassInMapping($resolver)) {
            try {
                return $this->createResolverByAccessor($resolver);
            } catch (NotFoundException $exception) {
                $this->logger->info($exception->getMessage());
            }
        }

        try {
            $resolver = array_key_first(
                array_filter(
                    $this->resolvers,
                    fn (ComponentResolver $resolver) => $resolver->complies($block, $block->getData('magewire'))
                )
            );

            $resolver = $this->createResolverByAccessor($resolver);
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            throw new ComponentResolverNotFoundException(
                sprintf('No component resolver complies for block %s.', $block->getNameInLayout())
            );
        }

        if ($resolver->remember()) {
            $this->cache($block, $resolver);
        }

        return $resolver;
    }

    /**
     * Create a resolver instance by its accessor.
     *
     * @throws NotFoundException
     */
    public function createResolverByAccessor(string $resolver): ComponentResolver
    {
        return $this->createResolverByType($this->getResolverClass($resolver), $resolver);
    }

    /**
     * @throws NotFoundException
     */
    public function createResolverByType(string $class, string $accessor): ComponentResolver
    {
        $instance = ObjectManager::getInstance()->create($class, [
            'accessor' => $accessor
        ]);

        if ($instance instanceof ComponentResolver) {
            return $instance;
        }

        throw new NotFoundException(__('No block resolver found'));
    }

    /**
     * Returns if the given resolver can be found by its accessor.
     */
    public function hasResolverClassInMapping(string $resolver): bool
    {
        try {
            return is_string($this->getResolverClass($resolver));
        } catch (NotFoundException $exception) {
            // WIP
        }

        return false;
    }

    /**
     * @template T of ComponentResolver
     * @return class-string<T>
     * @throws NotFoundException
     */
    private function getResolverClass(string $resolver): string
    {
        $cache = $this->magewireCache->resolvers()->fetch();
        $data  = $cache['resolvers'][$resolver] ?? null;

        if ($data) {
            return $data['class'];
        }
        if (class_exists($resolver)) {
            return $resolver;
        }

        if ($this->resolvers[$resolver]) {
            if (is_object($this->resolvers[$resolver])) {
                return $this->resolvers[$resolver]::class;
            }

            return $this->resolvers[$resolver];
        }

        throw new NotFoundException(__('No block resolver found for accessor "%s"', $resolver));
    }

    private function getBlockCacheKey(AbstractBlock $block, bool $unique = true): string
    {
        return $unique ? sprintf('%s_%s', $block->getCacheKey(), $block->getNameInLayout()) : $block->getCacheKey();
    }

    private function cache(AbstractBlock $block, ComponentResolver $resolver): ComponentResolver
    {
        $cache = $this->magewireCache->resolvers()->fetch();
        $cache['blocks'][$this->getBlockCacheKey($block)] = $resolver->getAccessor();

        $resolvers = $cache['resolvers'] ?? [$resolver->getAccessor() => []];

        $resolvers[$resolver->getAccessor()]['blocks'][] = $this->getBlockCacheKey($block);
        $resolvers[$resolver->getAccessor()]['class'] = $resolvers[$resolver->getAccessor()]['class'] ?? $resolver::class;
        $resolvers[$resolver->getAccessor()]['name'] = $resolvers[$resolver->getAccessor()]['name'] ?? $resolver->getAccessor();

        $cache['resolvers'] = $resolvers;

        // Attempt to cache the resolvers, regardless of whether caching is enabled or disabled.
        $this->magewireCache->resolvers()->save($cache);

        return $resolver;
    }
}
