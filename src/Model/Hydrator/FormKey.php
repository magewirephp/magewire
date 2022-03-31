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
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;
use Magento\Framework\Encryption\Helper\Security;
use Magento\Framework\App\RequestInterface as ApplicationRequestInterface;
use Magento\Framework\Data\Form\FormKey as ApplicationFormKey;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FormKey implements HydratorInterface
{
    protected ApplicationRequestInterface $request;
    protected ApplicationFormKey $formkey;

    /**
     * @param ApplicationRequestInterface $request
     * @param ApplicationFormKey $formkey
     */
    public function __construct(
        ApplicationRequestInterface $request,
        ApplicationFormKey $formkey
    ) {
        $this->request = $request;
        $this->formkey = $formkey;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if ($request->isSubsequent() && $this->compareFormKeys() === false) {
            throw new HttpException(419, 'Form key expired. Please refresh and try again.');
        }
    }

    /**
     * @inheritdoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        //
    }

    /**
     * Compare the X-CSRF-TOKEN with the current form key.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function compareFormKeys(): bool
    {
        return Security::compareStrings($this->request->getHeader('X-CSRF-TOKEN'), $this->formkey->getFormKey());
    }
}
