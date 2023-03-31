<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\Exception\LocalizedException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;
use Magento\Framework\App\RequestInterface as ApplicationRequestInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FormKey implements HydratorInterface
{
    protected ApplicationRequestInterface $request;
    protected SecurityHelper $securityHelper;

    public function __construct(
        ApplicationRequestInterface $request,
        SecurityHelper $securityHelper
    ) {
        $this->request = $request;
        $this->securityHelper = $securityHelper;
    }

    /**
     * @throws LocalizedException
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if ($request->isSubsequent() && $this->securityHelper->validateFormKey($this->request) === false) {
            throw new HttpException(419, 'Form key expired. Please refresh and try again.');
        }
    }

    // phpcs:ignore
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        //
    }
}
