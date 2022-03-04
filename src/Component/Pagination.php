<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Component;

use Magento\Framework\Exception\LocalizedException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentException;

/**
 * @method int getPage()
 * @method bool hasPage()
 *
 * @method int getPageSize()
 * @method bool hasPageSize()
 */
abstract class Pagination extends Component implements PaginationInterface
{
    public const COMPONENT_TYPE = 'pagination';

    public int $page = 1;
    public int $pageSize = 10;

    protected array $queryString = [
        'page',
        'pageSize'
    ];

    protected string $pagerTemplate = 'Magewirephp_Magewire::html/pagination/pager.phtml';

    /**
     * Renders a default pagination block.
     *
     * @param string|null $template
     * @return string
     */
    public function renderPagination(string $template = null): string
    {
        try {
            if (($parent = $this->getParent()) === null) {
                throw new ComponentException(__('No block attached onto the current Magewire component'));
            }
            if (($block = $parent->getLayout()->getBlock('magewire.pagination.pager')) === null) {
                throw new ComponentException(__('Pagination block could not be found'));
            }

            $block->setData('component', $this);
            $block->setTemplate($template ?? $this->pagerTemplate);

            return $block->toHtml();
        } catch (LocalizedException $exception) {
            return __('Pager not available.')->render();
        }
    }
}
