<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\ActionManager;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewFactory;
use Magewirephp\Magewire\Model\View\Utils;
use Magewirephp\Magewire\Support\DataScope;

class MagewireUnderscoreViewModel implements ArgumentInterface
{
    public function __construct(
        private ActionManager $actionManager,
        private DataScope $arguments,
        private Utils $utils,
        private ViewFactory $viewFactory
    ) {
        
    }

    public function action(string $class): ActionManager
    {
        return $this->actionManager->load($class);
    }

    public function arguments(): DataScope
    {
        return $this->arguments;
    }

    public function utils(): Utils
    {
        return $this->utils;
    }

    public function factory(): ViewFactory
    {
        return $this->viewFactory;
    }
}
