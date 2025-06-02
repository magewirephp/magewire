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
use function Magewirephp\Magewire\store;
use function Magewirephp\Magewire\trigger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\MagewireManager;
use Magewirephp\Magewire\MagewireServiceProvider;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Magewirephp\Magewire\Model\App\ExceptionManager;

class ViewBlockAbstractToHtmlBefore implements ObserverInterface
{
    public function __construct(
        private readonly MagewireManager $magewireManager,
        private readonly MagewireServiceProvider $magewireServiceProvider,
        private readonly ExceptionManager $exceptionManager
    ) {
        //
    }

    /**
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        $this->magewireServiceProvider->setup();

        /** @var AbstractBlock $block */
        $block = $observer->getData('block');
        /** @var mixed $magewire */
        $magewire = $block->getData('magewire');

        if ($magewire) {
            try {
                if ($magewire instanceof Component) {
                    // Update flag for use during subsequent updates to maintain synchronization and data consistency.
                    $update = store($magewire)->get('magewire:update', false);

                    if ($update) {
                        $this->handleUpdate($update, $block);

                        return;
                    }
                }

                /**
                 * Magewire has two trigger points for booting itself. One occurs during the rendering of a block.
                 * This is the only feasible location, after confirming we are not on an update request,
                 * where we should attempt to boot. A reboot is unlikely as the boot method includes a
                 * safety trigger to prevent such an occurrence. [1/2]
                 *
                 * @see \Magewirephp\Magewire\Controller\Router
                 */
                $this->magewireServiceProvider->boot();

                $construct = trigger('magewire:construct', $block);
                $block = $construct();

                $this->handleMount($block);
            } catch (Exception $exception) {
                $this->exceptionManager->handleWithBlock($block, $exception);
            }
        }
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     * @throws NotFoundException
     */
    private function handleUpdate(ComponentRequestContext $update, AbstractBlock $block): void
    {
        $this->magewireManager->update(
            $update->getSnapshot(),
            $update->getUpdates(),
            $update->getCalls(),
            $block
        );
    }

    /**
     * @throws NotFoundException
     * @throws RuntimeException
     */
    private function handleMount(AbstractBlock $block): void
    {
        /** @var Component $component */
        $component = $block->getData('magewire');

        $this->magewireManager->mount(
            $component->getName(),
            $component->resolver()->arguments()->forMount(),
            $block->getCacheKey(),
            $block,
            $component
        );
    }
}
