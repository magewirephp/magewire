<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Management;

use LogicException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Compiler;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\CompilerFactory;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\CompilerUtils;

class CompilerManager
{
    public function __construct(
        private DirectiveManager $directiveManager,
        private FileManager $fileManager,
        private CompilerFactory $compilerFactory,
        private CompilerUtils $compilerUtils
    ) {
    }

    public function directives(): DirectiveManager
    {
        return $this->directiveManager;
    }

    public function file(): FileManager
    {
        return $this->fileManager;
    }

    public function factory(): CompilerFactory
    {
        return $this->compilerFactory;
    }

    public function utils(): CompilerUtils
    {
        return $this->compilerUtils;
    }

    /**
     * Resolve the compiler for a Magewire component or an opted-in Magento block.
     */
    public function resolve(AbstractBlock $block): Compiler|null
    {
        $component = $block->getData('magewire');

        if (! $component instanceof Component && $block->getData('magewire:compile') !== true) {
            return null;
        }

        $compiler = $block->getData('magewire:compiler');

        if (! $compiler instanceof Compiler && $component instanceof Component) {
            $compiler = $component->magewireCompiler();
        }

        if (! $compiler instanceof Compiler) {
            $compiler = $this->factory()->newCompilerInstance();
        }

        if (! $compiler instanceof Compiler) {
            throw new LogicException(sprintf(
                'Compiler factory must create an instance of %s, got %s.',
                Compiler::class,
                get_debug_type($compiler)
            ));
        }

        $block->setData('magewire:compiler', $compiler);

        if ($component instanceof Component) {
            $component->magewireCompiler($compiler);
        }

        return $compiler;
    }
}
