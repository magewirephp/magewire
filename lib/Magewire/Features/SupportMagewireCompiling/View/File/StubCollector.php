<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management;

use Magento\Framework\Component\DirSearch;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\File;
use Magento\Framework\View\File\Collector\Base as BaseCollector;
use Magento\Framework\View\File\Factory as FileFactory;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewStub;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewStubFactory;

class StubCollector extends BaseCollector
{
    private bool $collected = false;
    private array $stubs = [];

    public function __construct(
        private readonly DirSearch $dirSearch,
        private readonly FileFactory $fileFactory,
        private readonly DesignInterface $design,
        private readonly ViewStubFactory $viewStubFactory,
    ) {
        parent::__construct(
            $this->dirSearch,
            $this->fileFactory,
            'stubs'
        );
    }

    public function collect(): array
    {
        if ($this->collected) {
            return $this->stubs;
        }

        /** @var array<int, File|ViewStub> $stubs */
        $stubs = $this->getFiles($this->design->getDesignTheme(), '*.stub');

        foreach ($stubs as $stub) {
            if ($stub instanceof File) {
                $stub = $this->viewStubFactory->create($stub);
            }

            $this->stubs[$stub->getNamespace()] = $stub;
        }

        // Flag as collected to implement lazy loading and avoid redundant disk access.
        $this->collected = true;

        return $this->stubs;
    }

    public function recollect(): array
    {
        $this->collected = false;

        return $this->collect();
    }
}
