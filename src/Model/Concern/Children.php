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

    public function getRenderedChildComponentId(string $id)
    {
        return $this->previouslyRenderedChildren[$id]['id'];
    }

    public function getRenderedChildComponentTagName(string $id)
    {
        return $this->previouslyRenderedChildren[$id]['tag'];
    }

    public function logRenderedChild(string $id, string $tag, string $cacheEntity = null): void
    {
        $this->renderedChildren[$cacheEntity ?? $id] = ['id' => $id, 'tag' => $tag];
    }

    public function preserveRenderedChild(string $id): void
    {
        $this->renderedChildren[$id] = $this->previouslyRenderedChildren[$id];
    }

    public function childHasBeenRendered(string $id): bool
    {
        return array_key_exists($id, $this->previouslyRenderedChildren);
    }

    public function setPreviouslyRenderedChildren(array $children): void
    {
        $this->previouslyRenderedChildren = $children;
    }

    public function getRenderedChildren(): array
    {
        return $this->renderedChildren;
    }
}
