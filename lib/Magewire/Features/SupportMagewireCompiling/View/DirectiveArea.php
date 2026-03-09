<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magewirephp\Magewire\Support\Factory;

/**
 * A directive area represents a technology-specific collection of directives grouped under a
 * shared prefix. These directives differ from global directives and only function when invoked
 * in camelCase format, such as "prefixArea".
 *
 * For example, in Magewire, the "magewire" prefix allows developers to create helpful utilities,
 * loops, and security-related directives, reducing the need for boilerplate code.
 */
class DirectiveArea
{
    private DirectiveResponsibilities|null $responsibilities = null;

    /**
     * @param array<string, Directive|class-string> $directives
     */
    public function __construct(
        private array $directives = []
    ) {
        
    }

    public function set(string $name, Directive $directive, bool $force = false): Directive
    {
        if ($this->has($name) && $force === false) {
            return $this->get($name);
        }

        return $this->directives[$name] = $directive;
    }

    public function unset(string $name): static
    {
        if ($this->has($name)) {
            unset($this->directives[$name]);
        }

        return $this;
    }

    public function has(string $directive): bool
    {
        return array_key_exists($directive, $this->directives);
    }

    public function responsibilities(): DirectiveResponsibilities
    {
        return $this->responsibilities ??= Factory::create(DirectiveResponsibilities::class);
    }

    public function get(string $directive): Directive|null
    {
        $type = $this->directives[$directive] ?? null;
        $standalone = is_string($type);

        if ($standalone) {
            $type = Factory::create($type);
        }

        /*
         * On a scoped directive, we need to make sure it closing or chaining responsibilities are
         * being memorized for when a directive
         */
        if ($type instanceof ScopeDirective) {
            $type = $standalone ? $type : Factory::create($type::class);

            foreach ($type->getResponsibilitiesFor($directive) as $responsibility) {
                if ($responsibility === $directive) { continue; }

$this->responsibilities()->push($responsibility, $type);
            }
        }

        return $type;
    }
}
