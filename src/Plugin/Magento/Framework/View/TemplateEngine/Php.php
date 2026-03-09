<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Plugin\Magento\Framework\View\TemplateEngine;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php as Subject;
use Magewirephp\Magento\Framework\View\RenderLifecycleManager;
use Magewirephp\Magewire\Component;

use function Magewirephp\Magewire\trigger;

class Php
{
    private array $components = [];

    public function __construct(
        private readonly RenderLifecycleManager $renderLifecycleManager
    ) {
    }

    function beforeRender(
        Subject $subject,
        BlockInterface $block,
        string $filename,
        array $dictionary = []
    ): array {
        $this->renderLifecycleManager->push($block);

        if ($block instanceof DataObject) {
            $magewire = $block->getData('magewire') ?? end($this->components);

            if ($magewire instanceof Component) {
                $dictionary['magewire'] = $magewire;

                if (! isset($this->components[$magewire->id()])) {
                    // The developer hooking into this event is responsible for pre-compilation.
                    // They must ensure that the callback they return provides an array containing
                    // $block, $filename, and $dictionary, as required by this plugin.
                    $render = trigger('magento:template:render', $block, $filename, $dictionary, $magewire);

                    $return = ['block' => $block, 'filename' => $filename, 'dictionary' => $dictionary];
                    $result = $render($return);

                    // Ensure that the component remains an instance of the Component class.
                    if (! ( $result['component'] ?? null ) instanceof Component) {
                        $result['component'] = $magewire;
                    }

                    $this->components[$magewire->id()] = $result;

                    // Re-assign original variables.
                    $block = $result['block'] ?? $block;
                    $filename = $result['filename'] ?? $filename;
                    $dictionary = $result['dictionary'] ?? $dictionary;
                }
            }
        }

        return [$block, $filename, $dictionary];
    }

    function afterRender(Subject $subject, string $html): string
    {
        $latest = end($this->components);

        if ($latest && ( $latest['component'] ?? null ) instanceof Component) {
            array_pop($this->components);

            // The developer hooking into this event is responsible for compilation.
            // They must ensure that the callback returns a string containing the final HTML,
            // which is represented by the $html variable.
            $finish = trigger('magento:template:rendered', $latest);

            $html = $finish($html);
        }

        $this->renderLifecycleManager->pop();
        return $html;
    }
}
