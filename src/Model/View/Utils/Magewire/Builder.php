<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils\Magewire;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exceptions\BuilderException;

class Builder
{
    /**
     * WIP...
     * @throws BuilderException
     */
    public function newComponent(string $name): Component
    {
        throw BuilderException::couldNotCreateComponent();
    }

    /**
     * WIP...
     * @throws BuilderException
     */
    public function newComponentFromBlock(AbstractBlock $block, string|null $name): Component
    {
        throw BuilderException::couldNotCreateComponent();
    }
}
