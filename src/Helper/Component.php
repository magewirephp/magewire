<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Exception\MissingComponentException;
use Magewirephp\Magewire\Component as MagewireComponent;
use Magewirephp\Magewire\Magewire\Test;
use Magewirephp\Magewire\Model\ComponentFactory;
use Magewirephp\Magewire\Model\ComponentResolver;

class Component
{
    protected ComponentFactory $componentFactory;
    protected ComponentResolver $componentResolver;

    public function __construct(
        ComponentFactory $componentFactory,
        ComponentResolver $componentResolver
    ) {
        $this->componentFactory = $componentFactory;
        $this->componentResolver = $componentResolver;
    }

    public function extractDataFromBlock(BlockInterface $block, array $addition = []): array
    {
        $magewire = $block->getData('magewire');

        if ($magewire && is_array($magewire)) {
            unset($magewire['type']);
            return array_merge_recursive($magewire, $addition);
        }

        return $addition;
    }
}
