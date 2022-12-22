<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

trait ReloadSectionData
{
    protected $reloadSectionData = true;

    /**
     * @return bool
     */
    public function getReloadSectionData(): bool
    {
        return $this->reloadSectionData;
    }
}
