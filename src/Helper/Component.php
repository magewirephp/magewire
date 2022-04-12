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
use Magewirephp\Magewire\Model\ComponentFactory;

class Component
{
    protected ComponentFactory $componentFactory;

    /**
     * @param ComponentFactory $componentFactory
     */
    public function __construct(
        ComponentFactory $componentFactory
    ) {
        $this->componentFactory = $componentFactory;
    }

    /**
     * @param Template $block
     * @param bool $init
     * @return MagewireComponent
     * @throws MissingComponentException
     */
    public function extractComponentFromBlock(Template $block, bool $init = false): MagewireComponent
    {
        $magewire = $block->getData('magewire');

        if ($magewire) {
            $component = is_array($magewire)
                ? $magewire['type'] : (is_object($magewire)
                    ? $magewire : $this->componentFactory->create());

            if ($component instanceof MagewireComponent) {
                if ($init) {
                    $component = $this->componentFactory->create($component);
                }

                $component->name = $block->getNameInLayout();
                $component->id = $component->id ?? $component->name;

                return $component->setParent($block);
            }
        }

        throw new MissingComponentException(__('Magewire component not found'));
    }

    /**
     * @param BlockInterface $block
     * @param array $addition
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
