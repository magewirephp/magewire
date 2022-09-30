<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

class LayoutRenderLifecycle
{
    /**
     * @var array<string, array{
     *     string: string
     *   }
     * >
     */
    private array $history = [];

    /**
     * @var array<string, null|string>
     */
    private array $views = [];

    private ?string $start = null;

    /**
     * Marks view as 'start rendering'
     *
     * @param string $name
     * @return $this
     */
    public function start(string $name): LayoutRenderLifecycle
    {
        if ($this->start === null) {
            $this->start = $name;
        }

        $this->views[$name] = null;
        return $this;
    }

    /**
     * Marks view as 'stop rendering'
     *
     * @param string $parent
     * @return $this
     */
    public function stop(string $parent): LayoutRenderLifecycle
    {
        $views = $this->getViews();
        $position = array_search($parent, array_keys($views), true);

        if ($position === false) {
            return $this;
        }

        $children = array_slice($views, $position + 1, count($views), true);

        // Special use case where a single component on the page doesn't have a child.
        if (isset($this->views[$parent]) && $parent === $this->start) {
            $children[$parent] = $this->views[$parent];
        }

        foreach ($children as $key => $value) {
            if ($parent === $this->start) {
                $this->history[$key] = $value;
            } else {
                $this->history[$parent][$key] = $value;
            }

            unset($this->views[$key]);
        }

        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return array_key_exists($name, $this->views);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function canStop(string $name): bool
    {
        return $name !== array_search($name, array_reverse($this->views), true) || $name === $this->start;
    }

    /**
     * @return array
     */
    public function getViews(): array
    {
        return $this->views;
    }

    /**
     * @return bool
     */
    public function hasHistory(): bool
    {
        return count($this->getHistory()) !== 0;
    }

    /**
     * @return array
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * @param string $tag
     * @param string $for
     * @return $this
     */
    public function setStartTag(string $tag, string $for): LayoutRenderLifecycle
    {
        $this->views[$for] = $tag;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isParent(string $name): bool
    {
        return array_search($name, array_keys($this->getViews()), true) === 0;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isChild(string $name): bool
    {
        return ! $this->isParent($name);
    }
}
