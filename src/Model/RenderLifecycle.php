<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

class RenderLifecycle
{
    private array $views = [];

    /**
     * Marks view as 'start rendering'
     *
     * @param string $name
     * @return $this
     */
    public function start(string $name): RenderLifecycle
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
    public function stop(string $parent): RenderLifecycle
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
     * @param string $name
     * @param bool $include
     * @return array
     */
    public function getViews(string $name, bool $include = false): array
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
    public function setStartTag(string $tag, string $for): RenderLifecycle
    {
        $this->views[$for] = $tag;
        return $this;
    }
}
