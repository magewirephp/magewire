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
use Magewirephp\Magewire\Model\LayoutRenderLifecycle;

class Php
{
    protected LayoutRenderLifecycle $layoutRenderLifecycle;

    /** @var Component[] $registry */
    private array $registry = [];

    /**
     * @param LayoutRenderLifecycle $layoutRenderLifecycle
     */
    public function __construct(
        LayoutRenderLifecycle $layoutRenderLifecycle
    ) {
        $this->layoutRenderLifecycle = $layoutRenderLifecycle;
    }

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
        if ($block instanceof DataObject && $block->hasData('magewire')) {
            $magewire = $block->getData('magewire');

            if ($magewire instanceof Component) {
                $dictionary['magewire'] = $magewire;
                $this->registry[$magewire->name] = $magewire;
            }
        } elseif (count($this->registry) !== 0) {
            $views  = $this->layoutRenderLifecycle->getViews();
            $latest = array_search(null, array_reverse($views), true);

            if (array_key_exists($latest, $this->registry) && $block instanceof DataObject) {
                $dictionary['magewire'] = $this->registry[$latest];
            }
        }

        return [$block, $filename, $dictionary];
    }
}
