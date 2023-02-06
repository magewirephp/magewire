<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Wireable;

use Magewirephp\Magewire\Model\WireableInterface;

/**
 * @deprecated don't use this, it's work in progress and maybe will- or will not make it.
 */
class WireableFile implements WireableInterface
{
    public $src;

    public function __construct(
        $src = []
    ) {
        $this->src = $src;
    }

    public function wire()
    {
        return $this->src;
    }

    public function unwire($value): WireableInterface
    {
        return new static($value);
    }
}
