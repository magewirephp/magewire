<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Management;

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
}
