<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

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
    /**
     * @param array<string, Directive> $directives
     */
    public function __construct(
        private array $directives = []
    ) {
        //
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

    public function replace(string $name, Directive $directive): Directive
    {
        if ($this->has($name)) {
            return $this->directives[$name] = $directive;
        }

        return $this->set($name, $directive);
    }

    public function has(string $directive): bool
    {
        return array_key_exists($directive, $this->directives);
    }

    public function get(string $directive): Directive|null
    {
        return $this->directives[$directive] ?? null;
    }
}
