<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

class TreeMaster
{
    protected array $tree = [];

    public function register(string $name)
    {
        $this->tree[$name] = null;
    }

    public function unregister(string $parent)
    {
        $children = $this->getComplete($parent, true);

        foreach ($children as $key => $value) {
            unset($this->tree[$key]);
        }
    }

    public function get(string $name)
    {
        return $this->tree[$name];
    }

    public function getComplete(string $name, bool $include = false): array
    {
        return array_filter($this->getTree(), function ($value, string $key) use ($name, $include) {
            if ((is_string($value) && $key !== $name) || ($key === $name && $include === true)) {
                return $value;
            }
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function inTree(string $name): bool
    {
        return array_key_exists($name, $this->getTree());
    }

    public function getTree(): array
    {
        return $this->tree;
    }

    public function registerTag(string $tag, string $for)
    {
        $this->tree[$for] = $tag;
    }
}
