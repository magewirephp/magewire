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

class Basic extends Component
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
        'Post #7',
        'Post #8',
        'Post #9'
    ];

    /**
     * @var string
     */
    public string $pageHookOutput = '';

    /**
     * @var string
     */
    public string $paginatorHookOutput = '';

    /**
     * Return the posts visible on the current page.
     *
     * @return string[]
     */
    public function getCurrentPosts(): array
    {
        $offset = ( (int) $this->getPage() - 1 ) * self::PER_PAGE;

        return array_slice(self::POSTS, $offset, self::PER_PAGE);
    }

    /**
     * Return the final available page.
     *
     * @return int
     */
    public function getLastPage(): int
    {
        return (int) ceil(count(self::POSTS) / self::PER_PAGE);
    }

    /**
     * Navigate to the final fixture page.
     *
     * @return void
     */
    public function goToLastPage(): void
    {
        $this->gotoPage($this->getLastPage());
    }

    /**
     * Record the paginator-specific lifecycle hook.
     *
     * @param int $page
     * @return void
     */
    public function updatedPage(int $page): void
    {
        $this->pageHookOutput = sprintf('page-is-set-to-%d', $page);
    }

    /**
     * Record the generic paginator lifecycle hook.
     *
     * @param int $page
     * @param string $pageName
     * @return void
     */
    public function updatedPaginators(int $page, string $pageName): void
    {
        $this->paginatorHookOutput = sprintf('%s-is-set-to-%d', $pageName, $page);
    }
}
