<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magewirephp\Magewire\Support\Distributor;
use Magewirephp\Magewire\Support\Pipeline;

/**
 * @method Pipeline template() Template compiler pipeline (main).
 * @method Pipeline html() Inline HTML compiler pipeline (support).
 */
class CompilerPipelines extends Distributor
{
    public function __construct(string $type = Pipeline::class)
    {
        parent::__construct($type);
    }
}
