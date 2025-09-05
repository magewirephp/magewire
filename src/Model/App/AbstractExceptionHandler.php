<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\App;

use Exception;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Exceptions\RequestException;

abstract class AbstractExceptionHandler
{
    function handle(Exception $exception, bool $subsequent = false): Exception|callable
    {
        if ($subsequent && ! ($exception instanceof RequestException)) {
            return new RequestException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }

        return $exception;
    }

    function handleWithBlock(AbstractBlock $block, Exception $exception, bool $subsequent = false): AbstractBlock
    {
        if ($block instanceof Template) {
            $block->setTemplate('Magewirephp_Magewire::magewire/exception.phtml');
        }

        return $block;
    }
}
