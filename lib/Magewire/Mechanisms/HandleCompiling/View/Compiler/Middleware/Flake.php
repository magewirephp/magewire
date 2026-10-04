<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Compiler\Middleware;

use Magewirephp\Magewire\Support\Random;

/**
 * `<flake:type>` tag compiler.
 *
 * Routes matched tags through the default `flake` Fragment Element which is
 * backed by the stock `FlakeFactory` (layout handle `magewire_flakes`). Use
 * this for component blocks defined in `view/.../layout/magewire_flakes.xml`.
 *
 * The prefix is constructor-configurable so another module can register the
 * same compiler through a virtual type without a product-specific subclass.
 */
class Flake extends AbstractTagCompiler
{
    private string $tagPrefix = 'flake';

    /**
     * @param string $tagPrefix
     */
    public function __construct(string $tagPrefix = 'flake')
    {
        $this->tagPrefix = $tagPrefix;
    }

    protected function prefix(): string
    {
        return $this->tagPrefix;
    }

    /**
     * @param array<string, mixed> $matches
     */
    protected function emitOpening(array $matches): string
    {
        $type = $matches['type'];
        $attributes = $this->parseParams($matches['attributes'] ?? '');
        $id = Random::alphabetical(5, true);
        $var = preg_replace('/[^a-zA-Z0-9]/', '_', $this->prefix() . ucfirst(strtolower($type)) . ucfirst($id));

        return "@magewireComponent(prefix: '{$this->prefix()}', id: '{$id}', variable: '{$var}', type: '{$type}')\n        " . $this->preamble($var, $attributes);
    }
}
