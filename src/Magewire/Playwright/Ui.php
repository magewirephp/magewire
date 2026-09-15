<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\WithPagination;

class Ui extends Component
{
    use WithPagination;

    private const ITEMS_PER_PAGE = 3;

    private const ITEMS = [
        'Reactive storefront components',
        'Framework-independent styles',
        'Theme compatibility layers',
        'Loading and activity states',
        'Notification presentation',
        'Exception diagnostics',
        'Pagination controls',
        'Browser test fixtures',
        'Accessible interaction states'
    ];

    public int $requests = 0;

    public string $draft = '';

    /**
     * @return string[]
     */
    public function getPaginationItems(): array
    {
        $offset = ( (int) $this->getPage() - 1 ) * self::ITEMS_PER_PAGE;

        return array_slice(self::ITEMS, $offset, self::ITEMS_PER_PAGE);
    }

    public function getPaginationLastPage(): int
    {
        return (int) ceil(count(self::ITEMS) / self::ITEMS_PER_PAGE);
    }

    public function simulateRequest(): void
    {
        usleep(750_000);

        $this->requests++;
    }
}
