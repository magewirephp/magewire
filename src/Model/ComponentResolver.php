<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Psr\Log\LoggerInterface;

class ComponentResolver
{
    protected ResolverInterface $default;
    protected LoggerInterface $logger;

    /** @var ResolverInterface[] $resolvers */
    protected array $resolvers = [];

    public function __construct(
        ResolverInterface $default,
        LoggerInterface $logger,
        array $resolvers = []
    ) {
        $this->default = $default;
        $this->logger = $logger;

        foreach ($resolvers as $resolver) {
            $this->resolvers[$resolver->getPublicName()] = $resolver;
        }

        if (array_key_exists($this->default->getPublicName(), $this->resolvers)) {
            unset($this->resolvers[$this->default->getPublicName()]);
        }
    }

    public function resolve(BlockInterface $block): Component
    {
        $resolvers = array_filter($this->resolvers, function (ResolverInterface $resolver) use ($block) {
            return $resolver->complies($block);
        });

        if (count($resolvers) > 1) {
            $this->logger->info('Magewire: Multiple block resolvers found, one expected.');
        }

        // At this point we can safely assume that the first one can be used.
        $resolver = array_values($resolvers)[0] ?? $this->default;

        return $resolver->construct($block)->setResolver($resolver);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function get(string $resolver): ResolverInterface
    {
        if ($this->resolvers[$resolver] ?? false) {
            return $this->resolvers[$resolver];
        } elseif ($this->default->getPublicName() === $resolver) {
            return $this->default;
        }

        // Typically this only applies when someone changed the resolver on the frontend.
        throw new NoSuchEntityException(__('Component resolver "%1s" does not exist.', $resolver));
    }
}
