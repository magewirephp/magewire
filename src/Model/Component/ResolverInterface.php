<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\MissingComponentException;
use Magewirephp\Magewire\Model\RequestInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

interface ResolverInterface
{
    /**
     * Returns a unique resolver name to be identified
     * with for reconstruction. This name is publicly
     * used in among other things, the fingerprint and
     * therefor can be visible to end users.
     *
     * Important: function always need to be implemented even
     * when inherited from another ResolverInterface.
     */
    public function getName(): string;

    /**
     * Checks for very specific data elements to see if
     * this block complies the resolver requirements.
     *
     * It's recommended to keep these checks a light as
     * possible e.g. without any database interactions.
     */
    public function complies(AbstractBlock $block): bool;

    /**
     * Construct a Magewire component based on type.
     */
    public function construct(AbstractBlock $block): Component;

    /**
     * Re-construct a Magewire component based on a subsequent request.
     *
     * @throws HttpException
     */
    public function reconstruct(RequestInterface $request): Component;
}
