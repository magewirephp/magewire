<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Observer\Frontend;

use Magewirephp\Magewire\Observer\ViewBlockAbstractToHtmlBefore as CurrentObserver;

/**
 * Keeps cached Magewire 1 event registrations compatible with the current render lifecycle.
 *
 * @deprecated Use \Magewirephp\Magewire\Observer\ViewBlockAbstractToHtmlBefore instead.
 * @see CurrentObserver
 */
class ViewBlockAbstractToHtmlBefore extends CurrentObserver
{
}
