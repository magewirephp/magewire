<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Component;

/**
 * Interface ActionInterface
 * @package Magewirephp\Magewire\Model\Component
 *
 * @api
 */
interface PaginationInterface
{
    /**
     * Navigation to the previous page.
     *
     * @return void
     */
    public function toPreviousPage(): void;

    /**
     * Navigate to the next page.
     *
     * @return void
     */
    public function toNextPage(): void;

    /**
     * Navigate to page.
     *
     * @param $page
     * @return void
     */
    public function toPage($page): void;

    /**
     * Get the total number of pages.
     *
     * @return int
     */
    public function getSize(): int;

    /**
     * Check if the current page is the first one.
     *
     * @return bool
     */
    public function onFirstPage(): bool;

    /**
     * Check if the current page is the last one.
     *
     * @return bool
     */
    public function onLastPage(): bool;

    /**
     * Check if the given page is the current page.
     *
     * @param $page
     * @return bool
     */
    public function isCurrentPage($page): bool;

    /**
     * Get the number of the last page.
     *
     * @return int
     */
    public function getLastPage(): int;

    /**
     * Returns if the pagination will show more than one page.
     *
     * @return bool
     */
    public function hasPages(): bool;

    /**
     * Check if there or any pages left based on the current page.
     *
     * @return bool
     */
    public function hasMorePages(): bool;

    /**
     * Get items for the current page.
     *
     * @return mixed
     */
    public function getPageItems();

    /**
     * Get all available items.
     *
     * @return mixed
     */
    public function getAllPageItems();

    /**
     * Render final pager HTML.
     *
     * @return string
     */
    public function renderPagination(): string;
}
