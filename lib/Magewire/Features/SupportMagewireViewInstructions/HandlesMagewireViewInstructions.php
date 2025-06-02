<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions;

use Magento\Framework\App\ObjectManager;

trait HandlesMagewireViewInstructions
{
    protected ?ViewInstructions $viewInstructions = null;

    public function viewInstructions()
    {
        return $this->viewInstructions ??= ObjectManager::getInstance()->create(ViewInstructions::class);
    }
}
