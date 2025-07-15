<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magento\Framework\View\File;
use Magento\Tests\NamingConvention\true\string;

class ViewStub
{
    private string|null $content = null;

    public function __construct(
        private readonly File $stub
    ) {
        //
    }

    public function getNamespace(): string
    {
        return strtolower(rtrim($this->stub->getName(), '.stub'));
    }

    public function getContent(): string
    {
        return $this->content ??= file_get_contents($this->stub->getFilename());
    }
}
