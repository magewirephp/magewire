<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magewire\Component as MagewireComponent;
use Magewirephp\Magewire\Exception\MissingComponentException;

/**
 * Class Component.
 */
class Component
{
    /**
     * @param BlockInterface $block
     *
     * @throws MissingComponentException
     *
     * @return MagewireComponent
     */
    public function extractComponentFromBlock(BlockInterface $block): MagewireComponent
    {
        $magewire = $block->getMagewire();

        if ($magewire) {
            if (is_array($magewire)) {
                return $magewire['type'];
            }
            if (is_object($magewire)) {
                return $magewire;
            }
        }

        throw new MissingComponentException(__('Magewire component not found'));
    }

    /**
     * @param BlockInterface $block
     * @param array          $addition
     *
     * @return array
     */
    public function extractDataFromBlock(BlockInterface $block, array $addition = []): array
    {
        $magewire = $block->getMagewire();

        if ($magewire && is_array($magewire)) {
            unset($magewire['type']);

            return array_merge_recursive($magewire, $addition);
        }

        return $addition;
    }
}
