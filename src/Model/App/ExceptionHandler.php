<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\App;

use Exception;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\View\Element\AbstractBlock;

class ExceptionHandler extends AbstractExceptionHandler
{
    function __construct(
        private readonly ApplicationState $state
    ) {
        //
    }

    function handleWithBlock(AbstractBlock $block, Exception $exception, bool $subsequent = false): AbstractBlock
    {
        $block = parent::handleWithBlock($block, $exception, $subsequent);
        $block->setData('application_state', $this->state);

        return $block;
    }
}
