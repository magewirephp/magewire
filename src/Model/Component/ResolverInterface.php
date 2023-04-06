<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\MissingComponentException;
use Magewirephp\Magewire\Model\RequestInterface;

interface ResolverInterface
{
    /**
     * Checks for very specific data elements to see if
     * this component complies the requirements.
     *
     * It's recommended to keep these checks a light as
     * possible e.g. without any database interactions.
     */
    public function complies(AbstractBlock $block): bool;

    /**
     * Construct a Magewire component based on type.
     */
    public function construct(Template $block): Component;

    /**
     * Re-construct a Magewire component based on a subsequent request.
     *
     * @throws MissingComponentException
     */
    public function reconstruct(RequestInterface $request): Component;
}
