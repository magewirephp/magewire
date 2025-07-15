<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use ReflectionClass;

abstract class ScopeDirective extends Directive
{
    private array $scopeResponsibilities = [];

    /**
     * @return string[]
     */
    public function getResponsibilitiesFor(string $directive): array
    {
        if (! ($this->scopeResponsibilities[$directive] ?? null)) {
            $reflection = new ReflectionClass($this);

            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(ScopeDirectiveChain::class);
                $attribute  = ($attributes[0] ?? null) ? $attributes[0]->newInstance() : null;

                if ($attribute) {
                    $this->scopeResponsibilities[$method->getName()] = $attribute->methods;
                }
            }
        }

        return $this->scopeResponsibilities[$directive] ?? [];
    }
}
