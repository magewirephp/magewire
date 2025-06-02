<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use Magewirephp\Magewire\MagewireServiceProvider;
use Magewirephp\Magewire\Model\Magento\System\ConfigMagewire as MagewireSystemConfig;
use Magewirephp\Magewire\Model\View\Utils\Magewire\Features as FeaturesViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Magewire\Mechanisms as MechanismsViewUtil;
use Magewirephp\Magewire\Model\View\UtilsInterface;

class Magewire implements UtilsInterface
{
    public function __construct(
        private readonly FeaturesViewUtil $features,
        private readonly MechanismsViewUtil $mechanisms,
        private readonly MagewireServiceProvider $magewireServiceProvider,
        private readonly MagewireSystemConfig $config
    ) {
        //
    }

    public function features(): FeaturesViewUtil
    {
        return $this->features;
    }

    public function mechanisms(): MechanismsViewUtil
    {
        return $this->mechanisms;
    }

    public function config(): MagewireSystemConfig
    {
        return $this->config;
    }

    public function getUpdateUri(): string
    {
        return '/magewire/update';
    }
}
