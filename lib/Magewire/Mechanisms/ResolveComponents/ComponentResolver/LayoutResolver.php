<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout;
use Magewirephp\Magento\View\Layout\GeneratorPool;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\AbstractArguments;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\LayoutArgumentsFactory;
use Magewirephp\Magewire\Support\Conditions;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function Magewirephp\Magewire\on;

class LayoutResolver extends ComponentResolver
{
    private array $handlesToInclude = [];

    protected string $accessor = 'layout';

    /**
     * @param array<string, bool> $handlesToExclude
     */
    public function __construct(
        private readonly Layout $layout,
        private readonly LayoutArgumentsFactory $argumentsFactory,
        private readonly Conditions $conditions,
        private readonly array $handlesToExclude
    ) {
        parent::__construct($this->accessor, $this->conditions);
    }

    /**
     * To create a Magewire component using regular Layout XML, you have three options for binding:
     *
     *   1. Bind the component directly to a child argument named "magewire" with a xsi:type of "object".
     *   This allows you to associate the component with the Magewire framework.
     *
     *   2. Bind the component as a Magewire argument with an xsi:type of "array".
     *   This array should contain a child item called "type" with an xsi:type of "object".
     *   This method also associates the component with the Magewire framework.
     *
     *   3. Bind the component using Option 2 as a template, but instead of assigning it an xsi:type of "object",
     *   set it as a "bool" with a value of true.
     *
     * This approach enables you to create the component dynamically, where you won't have a physical object
     * to control interaction, but you can still utilize it for tasks like polling data at regular intervals.
     *
     * By setting the value to true, you establish a connection with the Magewire framework without the need
     * for a specific object instance.
     */
    public function complies(mixed $block, mixed $magewire = null): bool
    {
        $this->conditions()->validate(fn () => $this->isBlock($block), 'is_block');

        return parent::complies($block, $magewire);
    }

    /**
     * @throws ComponentNotFoundException
     */
    public function construct(AbstractBlock $block): AbstractBlock
    {
        $magewire = $block->getData('magewire') ?? null;

        if (! $magewire) {
            throw new ComponentNotFoundException(
                sprintf('No component object found for "%1"', $block->getNameInLayout())
            );
        }

        // Magewire block can be directly passed as an argument or nested within a type argument.
        $component = is_array($magewire) ? $magewire['type'] : $magewire;

        if (! $component instanceof Component) {
            throw new ComponentNotFoundException(
                sprintf('Component "%s" could not be constructed', $block->getNameInLayout())
            );
        }

        // Fulfill the main promise to establish or reset a Component instance within the block.
        $block->setData('magewire', $component);

        // Register a dehydrate listener to attach necessary layout handles to the server memo.
        on('dehydrate', function ($component, ComponentContext $context) {
            $handles = array_diff(
                $context->getBlock()->getLayout()->getUpdate()->getHandles(),
                array_keys(array_filter($this->handlesToExclude))
            );

            $context->addMemo('handles', array_values($handles));
        });

        return $block;
    }

    /**
     * Layout reconstruction involves loading layout XML-generated blocks
     * based on the handles passed into the server memo during component construction.
     *
     * By this point, we can be certain that the layout handles accompany the
     * XHR request's server memo, stored in the snapshot object.
     *
     * After locating the block, standard construction is performed to complete
     * the process, creating the Magewire component as it would be during a regular page load.
     *
     * @throws ComponentNotFoundException|LocalizedException
     */
    public function reconstruct(ComponentRequestContext $request): AbstractBlock
    {
        $snapshot = $request->getSnapshot();

        $layout = $this->generateBlocks($snapshot->getMemoValue('handles', []));
        /** @var Template|false $block */
        $block = $layout->getBlock($snapshot->getMemoValue('name'));

        if ($block === false) {
            throw new HttpException(
                404,
                sprintf('Magewire component "%s" could not be found', $snapshot->getMemoValue('name'))
            );
        }

        // Now everything is prepared, we can simply re-call the construct method like during preceding requests.
        return $this->construct($block);
    }

    public function arguments(): AbstractArguments
    {
        return $this->arguments ??= $this->argumentsFactory->create();
    }

    /**
     * @throws RuntimeException
     */
    public function assemble(AbstractBlock $block, Component $component): AbstractBlock
    {
        /*
         * The block assembly occurs after the component is constructed, finalizing it with
         * necessary attributes such as name and ID. For the layout, the block's name and ID
         * are derived from the block itself, using the nameInLayout method provided by Layout XML.
         */
        $component->setName($block->getNameInLayout());
        $component->setId($block->getNameInLayout());

        return parent::assemble($block, $component);
    }

    /**
     * @throws LocalizedException
     */
    protected function generateBlocks(array $handles): Layout
    {
        $this->layout->setGeneratorPool(ObjectManager::getInstance()->get(GeneratorPool::class));
        $this->layout->getUpdate()->load($this->handlesToInclude + $handles);

        $this->layout->generateXml();
        $this->layout->generateElements();

        return $this->layout;
    }

    /**
     * Utility method: Allows classes extending Layout to set additional layout handles.
     * This method is typically used inside the reconstruct method.
     */
    protected function useLayoutHandle(string $handle): static
    {
        $this->handlesToInclude[] = $handle;

        return $this;
    }

    protected function isBlock(mixed $block): bool
    {
        return $block instanceof AbstractBlock;
    }
}
