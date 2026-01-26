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
use Magewirephp\Magewire\MagewireServiceProvider;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\ComponentResolverManager;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutManager;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function Magewirephp\Magewire\on;

class ResolveComponents
{
    public function __construct(
        private readonly ComponentResolverManager $componentResolverManagement,
        private readonly MagewireServiceProvider $magewireServiceProvider,
        private readonly LayoutManager $layoutManager
    ) {
        //
    }

    public function boot(): void
    {
        /*
         * IMPORTANT: by default, the layout singleton is bound onto a page by the given route. This singleton pattern
         * is being used systemwide throughout Magento (opinions aside).
         *
         * The problem here is, Magewire needs to be able to tell the layout singleton that it is allowed to fetch
         * dynamic, page unrelated, blocks based on any given layout handle(s).
         *
         * This means, unless there is a better way that it needs a customized Generator Pool and a
         * new builder that can make sure this is available only during subsequent Magewire requests.
         */
        if ($this->magewireServiceProvider->state()->mode()->isSubsequent()) {
            $this->layoutManager->decorator()->decorateForPagelessBlockFetching(
                $this->layoutManager->singleton()
            );
        }

        on('magewire:component:construct', function (AbstractBlock $block) {
            return $this->build(function () use ($block): array {
                $resolver = $this->componentResolverManagement->resolve($block);
                $block = $resolver->construct($block);

                return [$resolver, $block];
            });
        });

        on('magewire:component:reconstruct', function (ComponentRequestContext $request) {
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
        on('dehydrate', function (Component $component, ComponentContext $context) {
            $context->addMemo('resolver', $component->magewireResolver()->getAccessor());
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

            $component->magewireBlock($block);
            $component->magewireResolver($resolver);

            return $resolver->assemble($block, $component);
        };
    }
}
