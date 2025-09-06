<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Observer;

use Exception;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Component;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\App\ExceptionManager;
use Magewirephp\Magewire\MagewireManager;
use function Magewirephp\Magewire\store;

class ViewBlockAbstractToHtmlAfter implements ObserverInterface
{
    public function __construct(
        private readonly MagewireManager  $magewireManager,
        private readonly ExceptionManager $exceptionManager
    ) {
        //
    }

    /**
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var AbstractBlock $block */
        $block = $observer->getData('block');
        /** @var Component|mixed $magewire */
        $magewire = $block->getData('magewire');

        if ($magewire) {
            $transport = $observer->getData('transport');
            $html = $transport->getHtml();

            try {
                if (! $magewire instanceof Component) {
                    throw new ComponentNotFoundException('Something went wrong.');
                }

                if (! store($magewire)->get('magewire:update', false)) {
                    [$block, $html] = $this->magewireManager->render($block, $html);
                    $observer->setData('block', $block);
                }
            } catch (Exception $exception) {
                $block = $this->exceptionManager->handleWithBlock($block, $exception);
                // Making sure we do not end up in a cyclic event loop.
                $block->unsetData('magewire');

                $html = $block->toHtml();
            }

            $transport->setHtml($html);
        }
    }
}
