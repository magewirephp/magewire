<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\View\Fragment;
use Psr\Log\LoggerInterface;

class Component extends Fragment
{
    public function __construct(
        private readonly AbstractBlock $block,
        LoggerInterface $logger,
        Escaper $escaper,
        array $modifiers = []
    ) {
        parent::__construct($logger, $escaper, $modifiers);
    }

    public function block(): AbstractBlock
    {
        return $this->block;
    }
}
