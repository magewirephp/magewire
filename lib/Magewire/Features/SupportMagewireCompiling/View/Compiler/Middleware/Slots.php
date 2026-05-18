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
 * `<slot:name>` tag compiler.
 *
 * Slots emit a different scope directive (`@magewireSlot` / `@magewireEndSlot`)
 * than the component-emitting siblings — the underlying Slot Fragment Component
 * doesn't track a new area, just registers a named slot in the surrounding
 * one. The directive split keeps that lifecycle distinction explicit.
 *
 * Closing tags accept both `</slot:name>` and the bare `</slot>` form so
 * authors can pick whichever reads better; both compile to the same
 * `@magewireEndSlot`.
 */
class Slots extends AbstractTagCompiler
{
    protected function prefix(): string
    {
        return 'slot';
    }

    protected function closingDirective(): string
    {
        return '@magewireEndSlot';
    }

    protected function closingTagPattern(): string
    {
        // Accept both `</slot:name>` and the bare `</slot>` — Laravel-style
        // symmetry with Flux's slot tags.
        return "/<\/\s*slot(?::[\w\-.]+)?\s*>/";
    }

    protected function emitOpening(array $matches): string
    {
        $name = $matches['type'];
        $attributes = $this->parseParams($matches['attributes'] ?? '');
        $var = 'slot' . ucfirst(strtolower($name)) . ucfirst(Random::alphabetical(5, true));

        return "@magewireSlot(target: '{$name}', variable: '{$var}')\n        " . $this->preamble($var, $attributes);
    }
}
