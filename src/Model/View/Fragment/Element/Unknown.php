<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment\Element;

use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\View\Fragment;
use Magewirephp\Magewire\Model\View\SlotsRegistry;
use Psr\Log\LoggerInterface;

/**
 * @deprecated Work in progress, do not use in production.
 */
class Unknown extends Fragment\Element
{
    public function __construct(
        private ApplicationState $applicationState,
        string $variant,
        AbstractBlock $block,
        SlotsRegistry $slotsRegistry,
        LoggerInterface $logger,
        Escaper $escaper,
        array $modifiers = []
    ) {
        parent::__construct($variant, $block, $slotsRegistry, $logger, $escaper, $modifiers);
    }

    public function render(): string
    {
        $this->raw = sprintf('<!-- Unknown Magewire DOM element "%s". -->', $this->variant);

        if ($this->applicationState->getMode() === ApplicationState::MODE_PRODUCTION) {
            $this->raw = '';
        }

        return parent::render();
    }
}
