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

class Component
{
    protected ComponentFactory $componentFactory;

    public function __construct(
        ComponentFactory $componentFactory
    ) {
        $this->componentFactory = $componentFactory;
    }

    /**
     * @throws MissingComponentException
     */
    public function extractComponentFromBlock(Template $block, bool $init = false): MagewireComponent
    {
        $magewire = $block->getData('magewire');

        if ($magewire) {
            if (is_string($magewire)) {
                $resolve = explode('::', $magewire);

                if (count($resolve) === 2 && $resolve[0] === 'magento_widget') {
                    $magewire = $this->componentFactory->create(new Test);
                }
            }

            $component = is_array($magewire)
                ? $magewire['type'] : (is_object($magewire)
                    ? $magewire : $this->componentFactory->create());

            if ($component instanceof MagewireComponent) {
                if ($init) {
                    $component = $this->componentFactory->create($component);
                }

                $component->name = $block->getNameInLayout();
                $component->id = $component->id ?? $component->name;

                return $component->setParent($this->determineTemplate($block, $component));
            }
        }

        throw new MissingComponentException(__('Magewire component not found'));
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

    /**
     * Determines the template by a default template path
     * when the path is not defined within the layout.
     *
     * Results in: {Module_Name::magewire/dashed-class-name.phtml}
     */
    public function determineTemplate(Template $block, MagewireComponent $component): Template
    {
        if ($block->getTemplate() === null) {
            $module = explode('\\', get_class($component));

            $prefix = $module[0] . '_' . $module[1];
            $affix  = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', end($module)));

            $block->setTemplate($prefix . '::magewire/' . $affix . '.phtml');
        }

        return $block;
    }
}
