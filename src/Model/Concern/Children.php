<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

trait Children
{
    protected array $renderedChildren = [];
    protected array $previouslyRenderedChildren = [];

    /**
     * @param string $id
     * @return mixed
     */
    public function getRenderedChildComponentId(string $id)
    {
        return $this->previouslyRenderedChildren[$id]['id'];
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function getRenderedChildComponentTagName(string $id)
    {
        return $this->previouslyRenderedChildren[$id]['tag'];
    }

    /**
     * @param string $id
     * @param string $tag
     * @param string|null $cacheEntity
     */
    public function logRenderedChild(string $id, string $tag, string $cacheEntity = null)
    {
        $this->renderedChildren[$cacheEntity ?? $id] = ['id' => $id, 'tag' => $tag];
    }

    /**
     * @param string $id
     */
    public function preserveRenderedChild(string $id)
    {
        $this->renderedChildren[$id] = $this->previouslyRenderedChildren[$id];
    }

    /**
     * @param string $id
     * @return bool
     */
    public function childHasBeenRendered(string $id): bool
    {
        return in_array($id, array_keys($this->previouslyRenderedChildren), true);
    }

    /**
     * @param array $children
     */
    public function setPreviouslyRenderedChildren(array $children)
    {
        $this->previouslyRenderedChildren = $children;
    }

    /**
     * @return array
     */
    public function getRenderedChildren(): array
    {
        return $this->renderedChildren;
    }
}
