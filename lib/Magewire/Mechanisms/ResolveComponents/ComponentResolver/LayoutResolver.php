<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Snapshot;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\LayoutBlockArgumentsFactory;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutManager;
use Magewirephp\Magewire\Support\AttributesReader;
use Magewirephp\Magewire\Support\Conditions;
use Magewirephp\Magewire\Support\Factory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\store;

class LayoutResolver extends ComponentResolver
{
    protected string $accessor = 'layout';

    private LoggerInterface|null $logger = null;

    public function __construct(
        protected Conditions $conditions,
        protected LayoutBlockArgumentsFactory $layoutBlockArgumentsFactory,
        protected LayoutManager $layoutManager
    ) {
        parent::__construct($this->conditions);
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
            throw new ComponentNotFoundException(sprintf('No component object found for "%s"', $block->getNameInLayout()));
        }

        // Magewire block can be directly passed as an argument or nested within a type argument.
        $component = is_array($magewire) ? $magewire['type'] : $magewire;

        if (! $component instanceof Component) {
            throw new ComponentNotFoundException(sprintf('Component "%s" could not be constructed', $block->getNameInLayout()));
        }

        $this->handleBackwardsCompatibilityForComponent($magewire);
        // Fulfill the main promise to establish or reset a Component instance within the block.
        $block->setData('magewire', $component);

        // Register a dehydrate listener to attach necessary layout handles to the server memo.
        on('dehydrate', function (Component $component, ComponentContext $context) {
            $resolver = $component->magewireResolver();

            if ($resolver->getAccessor() === $this->getAccessor()) {
                if ($this->canMemorizeLayoutHandles()) {
                    $context->addMemo('handles', array_values($this->determineLayoutHandles($component, $context)));
                }

                if ($alias = $component->magewireBlock()->getData('magewire:alias')) {
                    $context->addMemo('alias', $alias);
                }
            }
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

        $alias = $snapshot->getMemoValue('alias');
        $name = $snapshot->getMemoValue('name');

        // Retrieve the layout handles that were stored on the context snapshot.
        $handles = $this->recoverLayoutHandles($snapshot);
        // Build the complete layout structure by processing the recovered handles into renderable blocks.
        $blocks = $this->generateBlocks($handles);

        /** @var Template|false $block */
        $block = $blocks[$alias ?? $name] ?? false;

        if ($block === false) {
            throw new HttpException(404, sprintf('Magewire component "%s" could not be found', $alias ?? $name));
        }

        if ($alias) {
            $block->setData('magewire:alias', $alias);
            $block->setNameInLayout($name);
        }

        // Now everything is prepared, we can simply re-call the construct method like during preceding requests.
        return $this->construct($block);
    }

    public function arguments(): MagewireArguments
    {
        return $this->arguments ??= $this->layoutBlockArgumentsFactory->create();
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
        $component->setAlias($component->getAlias() ?? $block->getData('magewire:alias'));

        $this->determineTemplate($block, $component);

        return parent::assemble($block, $component);
    }

    protected function logger(): LoggerInterface
    {
        return $this->logger ??= Factory::get(LoggerInterface::class);
    }

    /**
     * Determines the template by a default template path
     * when the path is not defined within the layout.
     *
     * Convention: {Vendor_Module::magewire/dashed-class-name.phtml}
     */
    protected function determineTemplate(AbstractBlock $block, Component $component): void
    {
        if ($block->getTemplate() !== null) {
            return;
        }

        $classParts = array_values(
            array_filter(
                explode('\\', get_class($component)),
                static fn (string $part) => $part !== 'Interceptor'
            )
        );

        $prefix = $classParts[0] . '_' . $classParts[1];
        $suffix = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', end($classParts)));

        $block->setTemplate($prefix . '::magewire/' . $suffix . '.phtml');
    }

    protected function generateBlocks(array $handles): array
    {
        $layout = $this->layoutManager->singleton();

        /**
         * Preferably, a total new instance of the layout should be used. But since this singleton is
         * used all over the place, it's very hard to manage a custom layout and have difference between
         * the two of them.
         *
         * This causes issues all over the place where we've tried to fix it using a Layout Manager
         * to provide developers with the correct layout object defaulting to the global singleton.
         *
         * But testing this for a while, didn't give a strong and solid foundation for future usage.
         * Therefore, we've decided to go with the global singleton for now.
         */
        if ($layout instanceof Layout) {
            $layout->getUpdate()->addHandle($handles);
            return $layout->getAllBlocks();
        }

        throw new \RuntimeException('Unable to generate layout');
    }

    protected function isBlock(mixed $block): bool
    {
        return $block instanceof AbstractBlock;
    }

    protected function determineLayoutHandles(Component $component, ComponentContext $context): array
    {
        return array_diff(
            $context->getBlock()->getLayout()->getUpdate()->getHandles(),
            ['default']
        );
    }

    protected function recoverLayoutHandles(Snapshot $snapshot): array
    {
        if ($this->canMemorizeLayoutHandles()) {
            return $snapshot->getMemoValue('handles') ?? [];
        }

        return [];
    }

    protected function canMemorizeLayoutHandles(): bool
    {
        return true;
    }

    protected function handleBackwardsCompatibilityForComponent(Component $component): void
    {
        $bc = store($component)->get('magewire:bc');

        try {
            $within = AttributesReader::for($component)->first(HandleBackwardsCompatibility::class);

            if ($within instanceof HandleBackwardsCompatibility) {
                $bc = $within->isBackwardsCompatible();
            }
        } catch (Exception $exception) {
            $this->logger()->critical($exception->getMessage(), ['exception' => $exception]);
        }

        store($component)->set('magewire:bc', $bc ?? false);
    }
}
