<?php

namespace Magewirephp\Magewire\Features\SupportPagination;

trait HandlesPagination
{
    public function getPage($pageName = 'page')
    {
        return $this->paginators[$pageName] ?? 1;
    }
}
