<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function Magewirephp\Magewire\on;

class ResolveComponents
{
    public function __construct(
        private readonly ComponentResolverManagement $componentResolverManagement
    ) {
        //
    }

    public function boot(): void
    {
        on('magewire:construct', function (AbstractBlock $block) {
            return $this->build(function () use ($block): array {
                $resolver = $this->componentResolverManagement->resolve($block);
                $block = $resolver->construct($block);

                return [$resolver, $block];
            });
        });

        on('magewire:reconstruct', function (ComponentRequestContext $request) {
            if (! $this->componentResolverManagement->hasResolverClassInMapping($request->getSnapshot()->getMemoValue('resolver'))) {
                throw new HttpException(400);
            }

            return $this->build(function () use ($request): array {
                $resolver = $this->componentResolverManagement->createResolverByAccessor($request->getSnapshot()->getMemoValue('resolver'));
                $block = $resolver->reconstruct($request);

                return [$resolver, $block];
            });
        });

        // Register a dehydrate listener to attach the used resolver accessor for easy reconstruction.
        on('dehydrate', function ($component, ComponentContext $context) {
            $context->addMemo('resolver', $component->resolver()->getAccessor());
        });
    }

    /**
     * @throws ComponentNotFoundException
     */
    protected function build(callable $builder): callable
    {
        [$resolver, $block] = $builder();

        if (! $block->getData('magewire') instanceof Component) {
            throw new ComponentNotFoundException(
                sprintf('Resolver "%s" failed to construct a Magewire component.', $resolver->getAccessor())
            );
        }

        return function () use ($resolver, $block) {
            $resolver->arguments()->assemble($block, true);

            /** @var Component $component */
            $component = $block->getData('magewire');

            $component->block($block);
            $component->resolver($resolver);

            return $resolver->assemble($block, $component);
        };
    }
}
