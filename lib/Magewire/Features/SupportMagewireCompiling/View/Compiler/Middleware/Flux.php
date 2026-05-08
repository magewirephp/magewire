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
 * `<flux:variant>` tag compiler — Laravel-Flux-flavoured.
 *
 * Inherits regex assembly, attribute parsing, and the fragment-lifecycle
 * preamble from {@see AbstractTagCompiler}. The Flux-specific quirks live
 * here: `slot=` attribute extraction and the `magewire_flux` factory route
 * via Fragment Element type `flux`.
 *
 * Free to diverge from Blade further as Laravel Flux evolves — the abstract
 * base only owns mechanics that are genuinely shared across every prefix.
 */
class Flux extends AbstractTagCompiler
{
    protected function prefix(): string
    {
        return 'flux';
    }

    protected function emitOpening(array $matches): string
    {
        $variant = $matches['variant'];
        $attributesRaw = $matches['attributes'] ?? '';

        // Strip a literal `slot="name"` so it doesn't end up as a regular
        // property on the component. Slot binding is a Flux convention not
        // shared with Blade — keep this transformation Flux-local.
        $attributesRaw = preg_replace(
            '/\s+slot\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/',
            '',
            $attributesRaw,
            1
        ) ?? $attributesRaw;

        $attributes = $this->parseParams($attributesRaw);
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
