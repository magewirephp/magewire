<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewStub;

class StubsManager
{
    public function __construct(
        private readonly StubCollector $collector
    ) {
        //
    }

    public function get(string $namespace): ViewStub|null
    {
        $stubs = $this->collector->collect();

        return $stubs[$namespace] ?? null;
    }
}
