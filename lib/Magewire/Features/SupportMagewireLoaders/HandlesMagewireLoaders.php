<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireLoaders;

use Magento\Framework\Phrase;

trait HandlesMagewireLoaders
{
    /** @var bool|string|Phrase|array */
    protected $loader = false;

    /**
     * @return bool|string|Phrase|array
     */
    public function getLoader()
    {
        return $this->loader;
    }
}
