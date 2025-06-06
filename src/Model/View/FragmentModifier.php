<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Throwable;

/**
 * Provides a secure decorator pattern for modifying fragment output within controlled boundaries.
 * Modifiers can only alter fragments through explicitly exposed modification points defined by
 * the fragment's API, preventing unauthorized or malicious changes to the rendered output.
 *
 * This approach maintains fragment integrity while enabling controlled customization - you can
 * only modify what the fragment author intentionally allows you to modify.
 */
abstract class FragmentModifier
{
    /**
     * Applies a transformation to the fragment.
     *
     * @throws Throwable
     */
    abstract public function modify(Fragment $fragment): Fragment;
}
