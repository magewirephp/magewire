<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment\Component;

use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\View\Fragment;
use Magewirephp\Magewire\Model\View\Management\SlotsManager;
use Psr\Log\LoggerInterface;

class Unknown extends Fragment\Component
{
    public function __construct(
        private ApplicationState $applicationState,
        string $type,
        AbstractBlock $block,
        SlotsManager $slotsManager,
        LoggerInterface $logger,
        Escaper $escaper,
        string $id,
        array $modifiers = []
    ) {
        parent::__construct($type, $block, $slotsManager, $logger, $escaper, $id, $modifiers);
    }

    public function render(): string
    {
        $this->raw = '';

        if ($this->applicationState->getMode() !== ApplicationState::MODE_PRODUCTION) {
            $this->raw = sprintf('<!-- Unknown component "%s". -->', $this->type());
        }

        return parent::render();
    }
}
