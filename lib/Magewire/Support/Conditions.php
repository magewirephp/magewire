<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Magewirephp\Magewire\Support\Conditions\ConditionEnum;

class Conditions
{
    /** @var array<int|string, callable|array<int|string, callable>> */
    private array $conditions = [];

    /**
     * @alias validate
     */
    public function if(callable $condition, string|null $name = null): static
    {
        return $this->set($condition, ConditionEnum::AND, $name);
    }

    public function validate(callable $condition, string|null $name = null): static
    {
        return $this->if($condition, $name);
    }

    public function and(callable $condition, string|null $name = null): static
    {
        return $this->set($condition, ConditionEnum::AND, $name);
    }

    public function or(callable|array $alternative, string|null $name = null): static
    {
        if (is_array($alternative)) {
            $alternative = array_filter($alternative, fn ($item) => is_callable($item));
        }

        return $this->set($alternative, ConditionEnum::OR, $name);
    }

    public function swap(string $name, ConditionEnum $from, ConditionEnum $to): static
    {
        $target = $this->get($from, $name);

        if (is_callable($target) && ! is_callable($to)) {
            $this->set($target, $to, $name);
        }

        return $this;
    }

    public function isset(string $name, ConditionEnum $type = ConditionEnum::AND): bool
    {
        return isset($this->conditions[$type->value][$name]);
    }

    public function unset(string $name, ConditionEnum|null $type = null): static
    {
        if ($type && isset($this->conditions[$type->value])) {
            unset($this->conditions[$type->value][$name]);

            return $this;
        }

        if (count($this->get(ConditionEnum::AND))) {
            $this->unset($name, ConditionEnum::AND);
        }
        if (count($this->get(ConditionEnum::OR))) {
            $this->unset($name, ConditionEnum::OR);
        }

        return $this;
    }

    public function evaluate(...$args): bool
    {
        foreach ($this->get(ConditionEnum::AND) as $condition) {
            if (! $condition(...$args)) {
                foreach ($this->get(ConditionEnum::OR) as $alternative) {
                    if (is_array($alternative) && $this->evaluateGroup($alternative, ...$args)) {
                        return true;
                    } else {
                        return $alternative(...$args);
                    }
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @return callable|array<int|string, callable>
     */
    private function get(ConditionEnum $type, string|null $name = null): callable|array
    {
        $type = $this->conditions[$type->value] ?? [];

        return $name ? $type[$name] : $type;
    }

    /**
     * @alias if
     */
    private function set(callable $condition, ConditionEnum $type, string|null $name = null): static
    {
        $name ?? count($this->get($type));
        $this->conditions[$type->value][$name] = $condition;

        return $this;
    }

    private function evaluateGroup(array $group, ...$args): bool
    {
        return array_reduce($group, fn ($carry, $item) => $carry && $item(...$args), true);
    }
}
