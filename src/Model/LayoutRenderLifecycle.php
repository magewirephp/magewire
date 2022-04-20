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
    private array $views = [];

    /**
     * Marks view as 'start rendering'
     *
     * @param string $name
     * @return $this
     */
    public function start(string $name): LayoutRenderLifecycle
    {
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
        $children = $this->getViewsWithFilter(function ($value, string $key) use ($parent) {
            if ((is_string($value) && $key !== $parent)) {
                return $value;
            }

            return false;
        });

        foreach ($children as $key => $value) {
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
        return $name !== array_key_last($this->views);
    }

    /**
     * @return array
     */
    public function getViews(): array
    {
        return $this->views;
    }

    /**
     * @param callable $filter
     * @param int $mode
     * @return array
     */
    public function getViewsWithFilter(callable $filter, int $mode = ARRAY_FILTER_USE_BOTH): array
    {
        return array_filter($this->views, $filter, $mode);
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
        return array_search($name, array_keys($this->getViews())) === 0;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isChild(string $name): bool
    {
        return !$this->isParent($name);
    }
}
