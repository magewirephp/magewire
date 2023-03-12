<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Helper\LayoutXml as LayoutXmlHelper;

class LayoutGenerateBlocksAfter implements ObserverInterface
{
    private LayoutXmlHelper $layoutXmlHelper;

    public function __construct(
        LayoutXmlHelper $layoutXmlHelper
    ) {
        $this->layoutXmlHelper = $layoutXmlHelper;
    }

    public function execute(Observer $observer)
    {
        /** @var LayoutInterface $layout */
        $layout = $observer->getData('layout');

        $this->layoutXmlHelper->setBlockNames(
            array_map(static function ($block) {
                return $block->getNameInLayout();
            }, $layout->getAllBlocks())
        );
    }
}
