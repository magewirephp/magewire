<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magewirephp\Magewire\Model\View\FragmentElementFactory;
use Magewirephp\Magewire\Model\View\FragmentFactory;

class ViewFactory
{
    public function __construct(
        private FragmentFactory $fragmentFactory
    ) {
        //
    }

    public function fragments(): FragmentFactory
    {
        return $this->fragmentFactory;
    }

    public function elements(): FragmentElementFactory
    {
        return $this->fragments()->elements();
    }
}
