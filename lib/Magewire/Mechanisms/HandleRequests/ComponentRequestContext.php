<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests;

use Magewirephp\Magewire\Mechanisms\HandleComponents\Snapshot;

class ComponentRequestContext
{
    /**
     * @param Snapshot $snapshot
     * @param mixed $updates
     * @param mixed $calls
     */
    function __construct(
        private Snapshot $snapshot,
        private mixed $calls = [],
        private mixed $updates = []
    ) {
        //
    }

    public function setCalls(array $calls): self
    {
        $this->calls = $calls;
        return $this;
    }

    function getCalls(): array
    {
        return $this->calls;
    }

    function setSnapshot(Snapshot $snapshot): self
    {
        $this->snapshot = $snapshot;
        return $this;
    }

    function getSnapshot(): Snapshot
    {
        return $this->snapshot;
    }

    public function setUpdates(array $updates = []): self
    {
        $this->updates = $updates;
        return $this;
    }

    function getUpdates(): array
    {
        return $this->updates;
    }
}
