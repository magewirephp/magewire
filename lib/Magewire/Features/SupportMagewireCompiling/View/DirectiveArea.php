<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magento\Framework\App\ObjectManager;

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
    private array $scopes = [];

    /**
     * @param array<string, Directive|class-string> $directives
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
        /*
         * Reuse the most recent scoped directive instance if one exists.
         *
         * Chained and ending directives (like @endforeach, @else) must execute
         * within the same class instance as their opening directive to maintain
         * proper state and context.
         */
        if (is_array($this->scopes[$directive] ?? null) && count($this->scopes[$directive]) !== 0) {
            return array_pop($this->scopes[$directive]);
        }

        $type = $this->directives[$directive] ?? null;
        $standalone = is_string($type);

        if ($standalone) {
            $type = ObjectManager::getInstance()->create($type);
        }

        if ($type instanceof ScopeDirective) {
            $type = $standalone ? $type : ObjectManager::getInstance()->create($type::class);

            foreach ($type->getResponsibilitiesFor($directive) as $responsibility) {
                $this->scopes[$responsibility][] = $type;
            }
        }

        return $type;
    }
}
