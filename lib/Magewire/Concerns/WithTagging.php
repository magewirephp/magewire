<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Concerns;

trait WithTagging
{
    /** @var array<int, string> */
    protected array $withTags = [];

    /**
     * Tag a fragment with a recognizable name.
     */
    public function withTag(string $tag): static
    {
        $sanitized = preg_replace('/[^a-z0-9]/', '', strtolower($tag));

        if ($sanitized !== '') {
            $this->withTags[] = $sanitized;
        }

        return $this;
    }

    /**
     * Clears all tags either completely or by a provided filter callback.
     */
    public function clearTags(callable|null $filter = null): static
    {
        $this->withTags = $filter ? array_filter($this->withTags, $filter) : [];

        return $this;
    }

    /**
     * Define multiple tags.
     */
    public function withTags(array $tags): static
    {
        foreach ($tags as $tag) {
            if ($this->hasTags([$tag])) {
                continue;
            }

            $this->withTag($tag);
        }

        return $this;
    }

    /**
     * Retrieve the fragment alias (if set).
     */
    public function getWithTags(): array
    {
        return $this->withTags;
    }

    /**
     * Retrieve if the fragment possesses an alias.
     */
    public function hasTags(array $tags = [], bool $strict = false): bool
    {
        if (empty($tags)) {
            return false;
        }
        if ($strict) {
            return empty(array_diff($tags, $this->withTags));
        }

        return ! empty(array_intersect($this->withTags, $tags));
    }
}
