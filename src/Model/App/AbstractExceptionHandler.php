<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\App;

use Exception;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Exceptions\RequestException;

abstract class AbstractExceptionHandler
{
    function handle(Exception $exception, bool $subsequent = false): Exception
    {
        if ($subsequent && ! ($exception instanceof RequestException)) {
            return new RequestException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }

        return $exception;
    }

    function handleWithBlock(AbstractBlock $block, Exception $exception, bool $subsequent = false): AbstractBlock
    {
        $block->setTemplate('Magewirephp_Magewire::magewire/exception.phtml');

        return $block;
    }
}
