<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use LogicException;
use Magewirephp\Magewire\Support\Random;
use ReflectionClass;

abstract class ScopeDirective extends Directive
{
    private array $scopeVariables = [];
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

    /**
     * Start a new scoped block and return the generated variable name.
     */
    protected function variableScopeStart(string|null $var = null): string
    {
        $var ??= Random::alphabetical(10);

        $this->scopeVariables[] = $var;

        return $var;
    }

    /**
     * End the most recent scope and return its variable name.
     */
    protected function variableScopeEnd(): string
    {
        if (empty($this->scopeVariables)) {
            throw new LogicException('Trying to end a scope without a matching start.');
        }

        return array_pop($this->scopeVariables);
    }

    /**
     * Get the current (most recent) scoped variable name without ending it.
     */
    protected function variableScope(): string|null
    {
        return $this->scopeVariables[count($this->scopeVariables) - 1] ?? null;
    }
}
