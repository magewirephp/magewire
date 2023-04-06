<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Cache\Type\Magewire as MagewireCache;
use Magewirephp\Magewire\Model\Component\Resolver\Layout as LayoutResolver;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Psr\Log\LoggerInterface;

class ComponentResolver
{
    protected LayoutResolver $default;
    protected LoggerInterface $logger;
    protected MagewireCache $cache;

    /** @var ResolverInterface[] $resolvers */
    protected array $resolvers = [];

    public function __construct(
        LayoutResolver $default,
        LoggerInterface $logger,
        MagewireCache $cacheMagewire,
        array $resolvers = []
    ) {
        $this->default = $default;
        $this->logger = $logger;
        $this->cache = $cacheMagewire;
        $this->resolvers = $resolvers;
    }

    /**
     * Resolve Magewire block and stick the resolver to it.
     *
     * @throws NoSuchEntityException
     */
    public function resolve(Template $block): Component
    {
        $resolver = $this->find($block);

        return $resolver->construct($block)->setResolver($resolver);
    }

    /**
     * Find a matching resolver who complies to the given block.
     *
     * @throws NoSuchEntityException
     */
    public function find(Template $block): ResolverInterface
    {
        $blockResolvers = $this->cache->load(MagewireCache::SECTION_RESOLVERS);
        $resolver = $blockResolvers[$block->getCacheKey()] ?? false;

        if ($resolver) {
            return $this->get($resolver);
        }

        $resolvers = array_filter($this->resolvers, function (ResolverInterface $resolver) use ($block) {
            return $resolver->complies($block);
        });

        if (count($resolvers) > 1) {
            $this->logger->info('Magewire: Multiple block resolvers found, one expected.');
        }

        // At this point we can safely assume that the first one can be used.
        $name = array_keys($resolvers)[0] ?? 'layout';
        $resolver = array_values($resolvers)[0] ?? $this->default;

        $blockResolvers[$block->getCacheKey()] = $name;
        $this->cache->saveResolvers($blockResolvers);

        return $resolver;
    }

    /**
     * Get resolver by name.
     *
     * @throws NoSuchEntityException
     */
    public function get(string $resolver): ResolverInterface
    {
        if ($this->resolvers[$resolver] ?? false) {
            return $this->resolvers[$resolver];
        } elseif ($resolver === 'layout') {
            return $this->default;
        }

        // Typically this only applies when someone changed the resolver on the frontend.
        throw new NoSuchEntityException(__('Block resolver "%1s" does not exist.', $resolver));
    }
}
