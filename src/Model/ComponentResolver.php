<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
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

    /**
     * @param array<string, ResolverInterface> $resolvers
     */
    public function __construct(
        LayoutResolver $default,
        LoggerInterface $logger,
        MagewireCache $cache,
        array $resolvers = []
    ) {
        $this->default = $default;
        $this->logger = $logger;
        $this->cache = $cache;

        foreach ($resolvers as $resolver) {
            $this->resolvers[$resolver->getName()] = $resolver;
        }

        if (! array_key_exists($this->default->getName(), $this->resolvers)) {
            $this->resolvers[$this->default->getName()] = $default;
        }
    }

    /**
     * Resolve Magewire block and stick the resolver to it.
     */
    public function resolve(AbstractBlock $block): Component
    {
        $resolver = $this->find($block);

        return $resolver->construct($block)->setResolver($resolver);
    }

    /**
     * Find a matching resolver who complies to the given block.
     */
    protected function find(AbstractBlock $block): ResolverInterface
    {
        $cache = $this->cache->load(MagewireCache::SECTION_RESOLVERS) ?: [];
        $resolver = $cache[$block->getCacheKey()] ?? false;

        if ($resolver) {
            try {
                return $this->get($resolver);
            } catch (NoSuchEntityException $exception) {
                $this->logger->info(
                    sprintf('Magewire: Resolver "%1s" is no longer present. Retrying available resolvers.', $resolver)
                );
            }
        }

        $resolvers = array_filter($this->resolvers, function (ResolverInterface $resolver) use ($block) {
            return $resolver->complies($block);
        });

        if (count($resolvers) > 1) {
            $this->logger->info('Magewire: Multiple block resolvers found, one expected.');
        }

        // It's safe to say the first one can be used, or we use the layout fallback.
        $name = array_keys($resolvers)[0];
        $resolver = array_values($resolvers)[0];

        $cache[$block->getCacheKey()] = $name;
        $this->cache->saveResolvers($cache);

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
        }

        // Typically this only applies when someone changed the resolver on the frontend.
        throw new NoSuchEntityException(__('Block resolver "%1" does not exist.', $resolver));
    }
}
