<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Plugin;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php;
use Magewirephp\Magewire\Component;

class MagewireTemplateVariable
{
    public function beforeRender(Php $subject, BlockInterface $block, $filename, array $dictionary = [])
    {
        if ($block instanceof DataObject && $block->hasData('magewire')) {
            $magewire = $block->getData('magewire');

            if ($magewire instanceof Component) {
                $dictionary['magewire'] = $magewire;
            }
        }
        return [$block, $filename, $dictionary];
    }
}
