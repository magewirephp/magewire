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
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;
use Magewirephp\Magewire\Model\Component\ResolverInterface;

class Layout implements ResolverInterface
{
    protected ResultPageFactory $resultPageFactory;
    protected EventManagerInterfac $eventManager;

    public function __construct(
        ResultPageFactory $resultPageFactory,
        EventManagerInterfac $eventManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->eventManager = $eventManager;
    }

    public function getNamespace(): string
    {
        return 'layout';
    }

    public function complies(BlockInterface $block): bool
    {
        return true;
    }

    public function build(BlockInterface $block): BlockInterface
    {
        return $block;
    }

    /**
     * @throws NotFoundException
     */
    public function rebuild(array $data): BlockInterface
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

        return $block;
    }
}
