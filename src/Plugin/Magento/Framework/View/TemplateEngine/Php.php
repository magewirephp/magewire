<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Plugin\Magento\Framework\View\TemplateEngine;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php as Subject;
use Magewirephp\Magewire\Component;

class Php
{
    /**
     * Automatically assign $magewire as template Block variable.
     *
     * @param Subject $subject
     * @param BlockInterface $block
     * @param string $filename
     * @param array $dictionary
     * @return array
     */
    public function beforeRender(
        Subject $subject,
        BlockInterface $block,
        string $filename,
        array $dictionary = []
    ): array {
        $magewire = $block->getData('magewire');

        if ($block instanceof DataObject && $magewire instanceof Component) {
            $dictionary['magewire'] = $magewire;
        }

        return [$block, $filename, $dictionary];
    }
}
