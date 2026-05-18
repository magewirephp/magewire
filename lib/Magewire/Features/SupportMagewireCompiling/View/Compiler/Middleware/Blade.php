<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler\Middleware;

use Magewirephp\Magewire\Support\Random;

/**
 * `<magewire:type>` tag compiler.
 *
 * Migrated from the legacy two-segment `<magewire-{component}:{type}>`
 * shape to the unified one-segment form so all built-in compilers share a
 * single tag pattern. Templates that used the old dash form must be updated
 * to drop the `-{component}` suffix.
 *
 * Emits the standard `@magewireComponent` directive with `type='magewire'`.
 */
class Blade extends AbstractTagCompiler
{
    protected function prefix(): string
    {
        return 'magewire';
    }

    protected function emitOpening(array $matches): string
    {
        $type = $matches['type'];
        $attributes = $this->parseParams($matches['attributes'] ?? '');
        $id = Random::alphabetical(5, true);
        $var = preg_replace('/[^a-zA-Z0-9]/', '_', $this->prefix() . ucfirst(strtolower($type)) . ucfirst($id));

        return "@magewireComponent(prefix: '{$this->prefix()}', id: '{$id}', variable: '{$var}', type: '{$type}')\n        " . $this->preamble($var, $attributes);
    }
}
