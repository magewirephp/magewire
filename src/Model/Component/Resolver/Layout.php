<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component\Resolver;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Component as MagewireComponent;
use Magewirephp\Magewire\Exception\MissingComponentException;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Magewirephp\Magewire\Model\ComponentFactory;
use Magewirephp\Magewire\Model\RequestInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Default Component Resolver.
 *
 * @api
 */
class Layout implements ResolverInterface
{
    protected ResultPageFactory $resultPageFactory;
    protected EventManagerInterface $eventManager;
    protected ComponentFactory $componentFactory;

    public function __construct(
        ResultPageFactory $resultPageFactory,
        EventManagerInterface $eventManager,
        ComponentFactory $componentFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->eventManager = $eventManager;
        $this->componentFactory = $componentFactory;
    }

    public function getName(): string
    {
        return 'layout';
    }

    public function complies(AbstractBlock $block): bool
    {
        return true;
    }

    /**
     * @throws MissingComponentException
     */
    public function construct(AbstractBlock $block): Component
    {
        $magewire = $block->getData('magewire');

        if ($magewire) {
            $component = is_array($magewire)
                ? $magewire['type'] : (is_object($magewire)
                    ? $magewire : $this->componentFactory->create());

            if ($component instanceof Component) {
//                if ($this->init) {
//                    $component = $this->componentFactory->create($component);
//                }

                $component->name = $block->getNameInLayout();
                $component->id = $component->id ?? $component->name;

                return $component->setParent($this->determineTemplate($block, $component));
            }
        }

        throw new MissingComponentException(__('Magewire component not found'));
    }

    /**
     * @throws MissingComponentException
     */
    public function reconstruct(RequestInterface $request): Component
    {
        $page = $this->resultPageFactory->create();
        $page->addHandle(strtolower($request->getFingerprint('handle')))->initLayout();

        /**
         * @deprecated this code is no longer supported and may cause issues if used.
         *             Please do not use it in the future.
         */
        $this->eventManager->dispatch('locate_wire_component_before', [
            'post' => $request->toArray(),
            'page' => $page
        ]);

        /** @var Template|false $block */
        $block = $page->getLayout()->getBlock($request->getFingerprint('name'));

        if ($block === false) {
            throw new HttpException(404, 'Magewire component "%1" could not be found');
        }

        return $this->construct($block);
    }

    /**
     * Determines the template by a default template path
     * when the path is not defined within the layout.
     *
     * Results in: {Module_Name::magewire/dashed-class-name.phtml}
     */
    protected function determineTemplate(BlockInterface $block, MagewireComponent $component): Template
    {
        /** @var Template $block */

        if ($block->getTemplate() !== null) {
            return $block;
        }

        $module = explode('\\', get_class($component));

        $prefix = $module[0] . '_' . $module[1];
        $affix  = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', end($module)));

        return $block->setTemplate($prefix . '::magewire/' . $affix . '.phtml');
    }
}
