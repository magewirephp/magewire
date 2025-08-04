<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver;

use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;
use Magewirephp\Magewire\Support\Conditions;

/**
 * The ComponentResolver serves as a base class for resolving and constructing Magewire components
 * from given blocks. It defines the logic for determining whether a block meets the necessary conditions
 * to be transformed into a component and provides a structured way to construct, reconstruct and assemble
 * components efficiently.
 */
abstract class ComponentResolver
{
    public const RESOLVER = 'magewire:resolver';

    /**
     * A unique accessor name that describes your resolver in a single word.
     *
     * This name is publicly visible and used during subsequent (XHR) requests
     * to retrieve the correct resolver and reconstruct the Magewire component.
     *
     * IMPORTANT: When extending this class (either directly or from a subclass),
     * ensure your resolver has a unique accessor name. The accessor name must
     * match the identifier used in the corresponding di.xml.
     */
    protected string $accessor = '';

    protected string|null $key = null;
    protected MagewireArguments|null $arguments = null;

    /** @var array<int|string, callable|array<int, callable>> $complyChecks */
    protected array $complyChecks = [];

    public function __construct(
        private readonly Conditions $conditions
    ) {
        //
    }

    /**
     * The complies method is intended to be a lightweight check that verifies whether the given block
     * meets the requirements to be constructed as a Magewire component. This can involve various conditions,
     * such as a specific data key being defined or a block belonging to a specific class.
     *
     * Aim to refine the requirements to their utmost specificity, minimizing the risk of conflicts
     * with potential other component resolvers. The overarching goal is to ensure seamless
     * integration and coexistence with various resolver modules.
     */
    public function complies(AbstractBlock $block, mixed $magewire = null): bool
    {
        if ($magewire) {
            $this->conditions()->if(fn () => $magewire instanceof Component, 'instanceof-component');
        }

        // Accept this as the resolver if the blocks data key is equal to the resolver accessor.
        $this->conditions()->or(fn () => $block->getData(self::RESOLVER) === $this->accessor, 'block-resolver-data-key');

        return $this->conditions()->evaluate($block, $magewire);
    }

    abstract public function arguments(): MagewireArguments;

    /**
     * After a block meets specific requirements, as verified by the compile method,
     * the block can be constructed.
     *
     * Ultimately, the block _must_ meet two specific criteria:
     *   1. An instance of \Magewirephp\Magewire\Component bound to the "magewire" block data key
     *   2. The component has bound "name" and "id"
     *
     * @throws \Magewirephp\Magewire\Exceptions\ComponentNotFoundException
     */
    abstract public function construct(AbstractBlock $block): AbstractBlock;

    /**
     * After the given snapshot has been verified, an attempt can be made to reconstruct the block,
     * including the component. At this point, only the snapshot data is available.
     *
     * Ultimately, the block _must_ meet two specific criteria:
     *   1. An instance of \Magewirephp\Magewire\Component bound to the "magewire" block data key
     *   2. The component has bound "name" and "id"
     *
     * It is recommended to have the reconstruction process invoke the original construct method.
     * This best practice helps prevent code duplication and maintains consistency between
     * preceding and subsequent components, fostering a more streamlined end result.
     */
    abstract public function reconstruct(ComponentRequestContext $request): AbstractBlock;

    /**
     * The accessor represents a unique public name used to retrieve the appropriate resolver
     * during the reconstruction process, essential for constructing both the block and the component.
     *
     * The accessor must match the DI-mapping Resolver item's name exactly.
     */
    public function getAccessor(): string
    {
        return $this->accessor;
    }

    /**
     * Assembles all the necessary components after a block has been constructed or reconstructed.
     * It is considered best practice to perform block assembly as the final step to ensure all
     * required Magewire elements are properly set.
     *
     * Responsible for the following tasks:
     *   1. Setting the block onto the component
     *   2. Setting the resolver onto the component
     *   3. Assembling data arguments
     *
     * @throws RuntimeException
     */
    public function assemble(AbstractBlock $block, Component $component): AbstractBlock
    {
        if ($this->key === null) {
            $this->key = $block->getCacheKey();
        }

        return $block;
    }

    /**
     * Flags whether the resolver should be reused (cached) or always re-evaluated.
     *
     * When the resolver is reused (cached), it improves performance by avoiding redundant
     * calculations, making it easier to determine which resolver needs to be used
     * for either the construction or reconstruction of the component.
     *
     * On the other hand, making the resolver dynamic (fluent) requires more processing,
     * but ensures that the component is resolved based on different terms or conditions
     * at runtime.
     */
    public function remember(): bool
    {
        return true;
    }

    protected function conditions(): Conditions
    {
        return $this->conditions;
    }
}
