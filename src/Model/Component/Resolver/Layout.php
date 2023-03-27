<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component\Resolver;

use Magento\Framework\Event\ManagerInterface as EventManagerInterfac;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Component as MagewireComponent;
use Magewirephp\Magewire\Exception\MissingComponentException;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Magewirephp\Magewire\Model\ComponentFactory;

class Layout implements ResolverInterface
{
    protected ResultPageFactory $resultPageFactory;
    protected EventManagerInterfac $eventManager;
    protected ComponentFactory $componentFactory;

    public function __construct(
        ResultPageFactory $resultPageFactory,
        EventManagerInterfac $eventManager,
        ComponentFactory $componentFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->eventManager = $eventManager;
        $this->componentFactory = $componentFactory;
    }

    public function complies(BlockInterface $block): bool
    {
        return true;
    }

    public function construct(BlockInterface $block): Component
    {
        $magewire = $block->getData('magewire');

        if ($magewire) {
            $component = is_array($magewire)
                ? $magewire['type'] : (is_object($magewire)
                    ? $magewire : $this->componentFactory->create());

            if ($component instanceof Component) {
//                if ($init) {
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
     * @throws NotFoundException
     * @throws MissingComponentException
     */
    public function reconstruct(array $data): Component
    {
        $page = $this->resultPageFactory->create();
        $page->addHandle(strtolower($data['fingerprint']['handle']))->initLayout();

        /** @deprecated this code is no longer supported and may cause issues if used. Please do not use it in the future. */
        $this->eventManager->dispatch('locate_wire_component_before', [
            'post' => $data,
            'page' => $page
        ]);

        $block = $page->getLayout()->getBlock($data['fingerprint']['name']);

        if ($block === false) {
            throw new NotFoundException(
                __('Magewire component "%1" could not be found', [$data['fingerprint']['name']])
            );
        }

        return $this->construct($block);
    }

    public function getPublicName(): string
    {
        return 'layout';
    }

    public function getMetaData(): ?array
    {
        return null;
    }

    /**
     * Determines the template by a default template path
     * when the path is not defined within the layout.
     *
     * Results in: {Module_Name::magewire/dashed-class-name.phtml}
     */
    protected function determineTemplate(Template $block, MagewireComponent $component): Template
    {
        if ($block->getTemplate() === null) {
            $module = explode('\\', get_class($component));

            $prefix = $module[0] . '_' . $module[1];
            $affix  = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', end($module)));

            $block->setTemplate($prefix . '::magewire/' . $affix . '.phtml');
        }

        return $block;
    }
}
