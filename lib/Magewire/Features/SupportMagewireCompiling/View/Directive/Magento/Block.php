<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Magento;

use Magento\Framework\App\Http\Context;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;

class Block extends Directive
{
    public function __construct(
        private readonly Context $userContext
    ) {
        //
    }

    public function child(string $alias): string
    {
        return "<?= \$block->getChildBlock({$alias}) ? \$block->getChildBlock({$alias})->toHtml() : '' ?>";
    }
}
