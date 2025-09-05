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
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php as Subject;
use Magewirephp\Magento\Framework\View\BlockRenderingRegistry;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;
use function Magewirephp\Magewire\trigger;

class Php
{
    private array $components = [];
    private array $magewireBlocks = [];

    public function __construct(
        private readonly MagewireViewModel      $magewireViewModel,
        private readonly BlockRenderingRegistry $renderRegistry,
    ) {
        //
    }

    function beforeRender(
        Subject $subject,
        BlockInterface $block,
        string $filename,
        array $dictionary = []
    ): array {
        $this->renderRegistry->push($block);

        [$block, $filename, $dictionary] = $this->registerMagewireVariableBefore($subject, $block, $filename, $dictionary);
        [$block, $filename, $dictionary] = $this->registerMagewireViewModelVariableBefore($subject, $block, $filename, $dictionary);

        return [$block, $filename, $dictionary];
    }

    function afterRender(Subject $subject, string $html): string
    {
        $html = $this->registerMagewireVariableAfter($subject, $html);
        $html = $this->registerMagewireViewModelVariableAfter($subject, $html);

        $this->renderRegistry->pop();
        return $html;
    }

    /**
     * Binds the $magewire variable as a globally accessible variable,
     * making it available for use within Magewire component templates.
     */
    private function registerMagewireVariableBefore(
        Subject $subject,
        BlockInterface $block,
        string $filename,
        array $dictionary = []
    ): array {
        if ($block instanceof DataObject) {
            $magewire = $block->getData('magewire') ?? end($this->components);

            if ($magewire instanceof Component) {
                $dictionary['magewire'] = $magewire;

                if (! isset($this->components[$magewire->id()])) {
                    // The developer hooking into this event is responsible for pre-compilation.
                    // They must ensure that the callback they return provides an array containing
                    // $block, $filename, and $dictionary, as required by this plugin.
                    $precompile = trigger('magewire:precompile', $block, $filename, $dictionary, $magewire);

                    $return = ['block' => $block, 'filename' => $filename, 'dictionary' => $dictionary];
                    $result = $precompile($return);

                    // Ensure that the component remains an instance of the Component class.
                    if (! ($result['component'] ?? null) instanceof Component) {
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

    private function registerMagewireVariableAfter(Subject $subject, string $html): string
    {
        $latest = end($this->components);

        if ($latest && ($latest['component'] ?? null) instanceof Component) {
            array_pop($this->components);

            // The developer hooking into this event is responsible for compilation.
            // They must ensure that the callback returns a string containing the final HTML,
            // which is represented by the $html variable.
            $finish = trigger('magewire:compiled', $latest);

            return $finish($html);
        }

        return $html;
    }

    /**
     * Ensures the Magewire view model is automatically bound to the block as a "view_model" data argument,
     * if it hasn't been set or is not an instance of the expected Magewire view model class.
     *
     * This reduces the need for manual binding, which is often repetitive since many sibling blocks depend on it.
     *
     * Why bind a view model instead of exposing a global template variable like $magewireViewModel?
     * Because when a block is moved outside its parent "magewire" wrapper, it still needs to maintain compatibility.
     * Using a shared view model ensures this compatibility without requiring changes to the template.
     *
     * Relying on global dictionary variables would force template modifications in such cases—something this method avoids.
     */
    private function registerMagewireViewModelVariableBefore(
        Subject $subject,
        BlockInterface $block,
        string $filename,
        array $dictionary = []
    ): array {
        if (($block instanceof AbstractBlock && $block->getNameInLayout() === 'magewire') || count($this->magewireBlocks) > 0) {
            if (! $block->getData('view_model') instanceof MagewireViewModel) {
                $block->setData('view_model', $this->magewireViewModel);
            }

            $this->magewireBlocks[] = $block;
        }

        return [$block, $filename, $dictionary];
    }

    private function registerMagewireViewModelVariableAfter(Subject $subject, $html): string
    {
        if (count($this->magewireBlocks) > 0) {
            array_pop($this->magewireBlocks);
        }

        return $html;
    }
}
