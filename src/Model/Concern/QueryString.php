<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

trait QueryString
{
    protected array $queryString = [];

    /**
     * @return array
     */
    public function getQueryString(): array
    {
        return $this->queryString;
    }

    /**
     * @return bool
     */
    public function hasQueryString(): bool
    {
        return !empty($this->getQueryString());
    }
}
