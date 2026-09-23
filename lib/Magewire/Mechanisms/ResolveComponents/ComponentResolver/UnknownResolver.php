<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\BlockMagewireArgumentsFactory;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;
use Magewirephp\Magewire\Support\Conditions;
use Psr\Log\LoggerInterface;

/**
 * Last-resort fallback resolver.
 *
 * The {@see \Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\ComponentResolverManager}
 * falls back to the "unknown" accessor when no registered resolver complies with a block that
 * nevertheless carries "magewire" data. Before this resolver existed, that fallback threw a cryptic
 * "No block resolver found for accessor 'unknown'" exception, hiding the real problem: the block was
 * never recognised by any resolver.
 *
 * This resolver exists purely to make that situation explicit. It never auto-selects itself
 * ({@see complies()} always returns false) and it is never cached ({@see remember()} returns false),
 * so once the offending block is fixed the correct resolver is picked up again. When reached, it logs
 * an actionable warning naming the block and then throws a clear exception, because a fallback resolver
 * genuinely has no way to construct a component.
 */
class UnknownResolver extends ComponentResolver
{
    protected string $accessor = 'unknown';

    public function __construct(
        protected Conditions $conditions,
        protected BlockMagewireArgumentsFactory $blockMagewireArgumentsFactory,
        protected LoggerInterface $logger
    ) {
        parent::__construct($this->conditions);
    }

    /**
     * Never comply automatically. This resolver is only ever reached through the manager's explicit
     * "unknown" fallback accessor, never by iterating registered resolvers.
     */
    public function complies(AbstractBlock $block, mixed $magewire = null): bool
    {
        return false;
    }

    /**
     * @throws ComponentNotFoundException
     */
    public function construct(AbstractBlock $block): AbstractBlock
    {
        $name = $block->getNameInLayout() ?: $block->getCacheKey();

        $this->logger->warning(sprintf(
            'Magewire: no component resolver complied for block "%s"; falling back to the "unknown" resolver. '
            . 'The block carries "magewire" data but no registered resolver recognises it. Check the block\'s '
            . '"magewire" / "magewire:resolver" arguments, or register a resolver whose complies() matches it.',
            $name
        ));

        throw new ComponentNotFoundException(sprintf('No component resolver could construct the Magewire component for block "%s".', $name));
    }

    /**
     * @throws ComponentNotFoundException
     */
    public function reconstruct(ComponentRequestContext $request): AbstractBlock
    {
        $name = $request->getSnapshot()->getMemoValue('name') ?? 'unknown';

        $this->logger->warning(sprintf(
            'Magewire: subsequent request resolved to the "unknown" fallback resolver for component "%s". '
            . 'The originating resolver could not be determined, so the component cannot be reconstructed.',
            $name
        ));

        throw new ComponentNotFoundException(sprintf('No component resolver could reconstruct the Magewire component "%s".', $name));
    }

    public function arguments(): MagewireArguments
    {
        return $this->arguments ??= $this->blockMagewireArgumentsFactory->create();
    }

    /**
     * Never cache the fallback, so the correct resolver is re-evaluated once the block is fixed.
     */
    public function remember(): bool
    {
        return false;
    }
}
