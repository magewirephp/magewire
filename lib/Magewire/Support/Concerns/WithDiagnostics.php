<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\Concerns;

use Magewirephp\Magewire\Support\Diagnostics;

trait WithDiagnostics
{
    use WithFactory;

    // Fabricates (Lazy Initialization pattern).
    private Diagnostics|null $withDiagnostics = null;

    public function diagnostics(): Diagnostics
    {
        return $this->withDiagnostics ??= $this->newTypeInstance(Diagnostics::class);
    }
}
