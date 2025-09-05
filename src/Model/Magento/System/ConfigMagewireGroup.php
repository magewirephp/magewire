<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Magento\System;

abstract class ConfigMagewireGroup
{
    public function __construct(
        private readonly ConfigMagewire $config
    ) {
        //
    }

    public function config(): ConfigMagewire
    {
        return $this->config;
    }
}
