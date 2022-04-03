<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * @api
 */
interface WireableInterface extends ArgumentInterface
{
    /**
     * @return array|string|int|bool
     */
    public function wire();

    /**
     * @param $value
     * @return WireableInterface
     */
    public function unwire($value): WireableInterface;
}
