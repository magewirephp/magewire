<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

trait Method
{
    /**
     * Protected methods.
     *
     * @see getUncallables()
     * @var string[]
     */
    protected $uncallables = [];

    /**
     * Returns an optional array with uncallable method names
     * who can not be executed by a subsequent request.
     *
     * These methods are still callable inside the component's template file.
     *
     * @return string[]
     */
    public function getUncallables(): array
    {
        return $this->uncallables;
    }
}
