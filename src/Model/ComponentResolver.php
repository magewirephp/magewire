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
use Magewirephp\Magewire\Controller\Post\Livewire;
use Magewirephp\Magewire\Model\Component\Resolver\Layout as LayoutResolver;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Psr\Log\LoggerInterface;

class ComponentResolver
{
    protected ResolverInterface $default;
    protected LoggerInterface $logger;

    /** @var ResolverInterface[] $resolvers */
    protected array $resolvers = [];

    public function __construct(
        LayoutResolver $default,
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

    public function resolve(Template $block): Component
    {
        /**
         * @todo this could be cached so it doesnt have to check for the right resolver each
         *       time a block comes in during a preceding request. Why only during a preceding
         *       request you might ask? Simply because the message controller gets the right
         *       resolver based on the required name in the fingerprint.
         *
         * @see Livewire::locateWireComponent()
         */
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
        throw new NoSuchEntityException(__('Block resolver "%1s" does not exist.', $resolver));
    }
}
