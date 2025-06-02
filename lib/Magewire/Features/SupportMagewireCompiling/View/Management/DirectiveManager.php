<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\DirectiveArea;

class DirectiveManager
{
    /**
     * @param array<string, DirectiveArea> $areas
     */
    public function __construct(
        private readonly DirectiveArea $directives,
        private array $areas = []
    ) {
        //
    }

    public function area(string|null $name = null, DirectiveArea|null $area = null): DirectiveArea|null
    {
        if (is_string($name) && strlen($name) !== 0) {
            if ($area) {
                return $this->areas[$name] = $area;
            }

            return $this->areas[$name] ?? null;
        }

        return $this->directives;
    }

    public function tryToLocateArea(string $subject): array
    {
        foreach ($this->areas as $area => $object) {
            if (str_starts_with($subject, $area)) {
                return [$object, lcfirst(substr($subject, strlen($area)))];
            }
        }

        return [null, $subject];
    }
}
