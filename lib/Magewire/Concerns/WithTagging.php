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
    private array $tags = [];

    /**
     * Tag a fragment with a recognizable name.
     */
    public function tag(string $name): static
    {
        $sanitized = preg_replace('/[^a-z0-9]/', '', strtolower($name));

        if ($sanitized !== '') {
            $this->tags[] = $sanitized;
        }

        return $this;
    }

    /**
     * Clears all tags either completely or by a provided filter callback.
     */
    public function clearTags(callable|null $filter = null): static
    {
        $this->tags = $filter ? array_filter($this->tags, $filter) : [];

        return $this;
    }

    /**
     * Returns if the given tag exists.
     */
    public function hasTag(string $name): bool
    {
        return is_string($this->tags[$name] ?? null);
    }

    /**
     * Returns if any tags were set.
     */
    public function hasTags(): bool
    {
        return count($this->tags) > 0;
    }
}
