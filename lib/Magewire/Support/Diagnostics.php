<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Magewirephp\Magewire\Support\Concerns\WithFactory;
use Throwable;

/**
 * Runtime Diagnostics (WIP)
 *
 * Collects and stores diagnostic information during component execution,
 * including error logs, metadata, and operational metrics. This class serves
 * as a centralized tracking mechanism for debugging and monitoring purposes.
 *
 * Features:
 * - Error and exception logging
 * - Metadata storage for execution context
 * - Diagnostic report generation
 */
class Diagnostics extends Metadata
{
    use WithFactory;

    const METADATA = '__metadata';

    public function log(string|Throwable $message): static
    {
        $message = $message instanceof Throwable ? $message->getMessage() : $message;
        $this->metadata()->push('logs', $message);

        return $this;
    }

    /**
     * Metadata entry.
     */
    public function metadata(): Metadata
    {
        return $this->data()->get(self::METADATA);
    }

    public function report(): array
    {
        return array_merge($this->data()->all(), ['__metadata' => $this->metadata()]);
    }

    protected function data(): DataArray
    {
        return $this->data ??= $this->newTypeInstance(DataArray::class)
            ->set(self::METADATA, $this->newTypeInstance(Metadata::class)
                ->data()->defaults([
                    'logs' => []
                ]));
    }
}
