<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportPagination;

use function Magewirephp\Magewire\invade;
use Magewirephp\Magewire\WithPagination;
use Magewirephp\Magewire\Features\SupportQueryString\SupportQueryString;
use Magewirephp\Magewire\ComponentHookRegistry;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Livewire;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Cursor;
class SupportPagination extends ComponentHook
{
    protected $restoreOverriddenPaginationViews;
    function skip()
    {
        return !in_array(WithPagination::class, class_uses_recursive($this->component));
    }
}