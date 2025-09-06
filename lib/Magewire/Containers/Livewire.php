<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Containers;

use Magewirephp\Magewire\MagewireManager;

/**
 * The Livewire container class allows the use of app('livewire') with only the app
 * function needing to be imported into the required class. Having a separate container
 * apart from the MagewireManager provides the flexibility to make specific changes for
 * the app method without modifying the MagewireManager itself, while still leveraging
 * its functionality.
 */
class Livewire extends MagewireManager
{
}
