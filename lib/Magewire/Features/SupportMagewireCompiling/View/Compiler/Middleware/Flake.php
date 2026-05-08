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
 * `<flake:variant>` tag compiler.
 *
 * Routes matched tags through the default `flake` Fragment Element which is
 * backed by the stock `FlakeFactory` (layout handle `magewire_flakes`). Use
 * this for component blocks defined in `view/.../layout/magewire_flakes.xml`.
 *
 * Sibling of {@see Flux} — same emission shape with `type='flake'` instead of
 * `type='flux'`. The split exists so each prefix can map to its own layout
 * handle and resolver, keeping flake-vs-flux components distinct during
 * AJAX rehydrate.
 */
class Flake extends AbstractTagCompiler
{
    protected function prefix(): string
    {
        return 'flake';
    }

    protected function emitOpening(array $matches): string
    {
        $variant = $matches['variant'];
        $attributes = $this->parseParams($matches['attributes'] ?? '');
        $id = Random::alphabetical(5, true);
        $var = preg_replace(
            '/[^a-zA-Z0-9]/',
            '_',
            $this->prefix() . ucfirst(strtolower($variant)) . ucfirst($id)
        );

        return "@magewireComponent(type: '{$this->prefix()}', id: '{$id}', variable: '{$var}', variant: '{$variant}')\n        "
            . $this->preamble($var, $attributes);
    }
}
