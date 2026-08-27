<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\Pagination;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\WithPagination;

class Multiple extends Component
{
    use WithPagination;

    private const PER_PAGE = 3;

    private const POSTS = [
        'Post #1',
        'Post #2',
        'Post #3',
        'Post #4',
        'Post #5',
        'Post #6',
    ];

    private const ITEMS = [
        'Item #1',
        'Item #2',
        'Item #3',
        'Item #4',
        'Item #5',
        'Item #6',
    ];

    /**
     * @var string
     */
    public string $pageHookOutput = '';

    /**
     * @var string
     */
    public string $itemPageHookOutput = '';

    /**
     * Return the posts visible on the default paginator page.
     *
     * @return string[]
     */
    public function getCurrentPosts(): array
    {
        return $this->slicePage(self::POSTS, (int) $this->getPage());
    }

    /**
     * Return the items visible on the named paginator page.
     *
     * @return string[]
     */
    public function getCurrentItems(): array
    {
        return $this->slicePage(self::ITEMS, (int) $this->getPage('item-page'));
    }

    /**
     * Navigate the named item paginator backwards.
     *
     * @return void
     */
    public function previousItemPage(): void
    {
        $this->previousPage('item-page');
    }

    /**
     * Navigate the named item paginator forwards.
     *
     * @return void
     */
    public function nextItemPage(): void
    {
        $this->nextPage('item-page');
    }

    /**
     * Record the default paginator lifecycle hook.
     *
     * @param int $page
     * @return void
     */
    public function updatedPage(int $page): void
    {
        $this->pageHookOutput = sprintf('page-is-set-to-%d', $page);
    }

    /**
     * Record the kebab-cased paginator lifecycle hook.
     *
     * @param int $page
     * @return void
     */
    public function updatedItemPage(int $page): void
    {
        $this->itemPageHookOutput = sprintf('item-page-is-set-to-%d', $page);
    }

    /**
     * Slice a deterministic fixture data set for a paginator page.
     *
     * @param string[] $values
     * @param int $page
     * @return string[]
     */
    private function slicePage(array $values, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        return array_slice($values, $offset, self::PER_PAGE);
    }
}
