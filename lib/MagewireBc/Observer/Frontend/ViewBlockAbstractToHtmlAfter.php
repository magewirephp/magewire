<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Observer\Frontend;

use Magewirephp\Magewire\Observer\ViewBlockAbstractToHtmlAfter as CurrentObserver;

/**
 * Keeps cached Magewire 1 event registrations compatible with the current render lifecycle.
 *
 * @deprecated Use \Magewirephp\Magewire\Observer\ViewBlockAbstractToHtmlAfter instead.
 * @see CurrentObserver
 */
class ViewBlockAbstractToHtmlAfter extends CurrentObserver
{
}
