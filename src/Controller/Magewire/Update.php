<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller\Magewire;

use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\Helper\Security;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magewirephp\Magento\Controller\MagewireUpdateResult;
use Magewirephp\Magento\Controller\MagewireUpdateResultFactory;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Facade\HandleRequestFacade;
use Magewirephp\Magewire\Model\App\ExceptionManager;
use Magewirephp\Magewire\MagewireServiceProvider;

class Update implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly MagewireServiceProvider $magewireServiceProvider,
        private readonly ExceptionManager $exceptionManager,
        private readonly MagewireUpdateResultFactory $magewireUpdateResultFactory,
        private readonly ApplicationState $applicationState,
        private readonly MagewireUpdateResultFactory $updateResultFactory,
        private readonly FormKey $formKey
    ) {
        //
    }

    /**
     * @throws NoSuchEntityException
     * @throws ComponentNotFoundException
     * @throws NotFoundException
     * @throws Exception
     */
    public function execute(): MagewireUpdateResult
    {
        try {
            /*
             * Passes both the components to be updated and the CSRF token (Form Key) to the "Request Handler"
             * mechanism via the Request object. The Request Handler triggers the regular progress,
             * identical to the original, when rendering a regular page.
             */
            /** @var HandleRequestFacade $handleRequestsMechanismFacade */
            $handleRequestsMechanismFacade = $this->magewireServiceProvider->getHandleRequestsMechanismFacade();

            return $this->updateResultFactory->create(
                $handleRequestsMechanismFacade->update()
            );
        } catch (Exception $exception) {
            try {
                $this->exceptionManager->handle($exception);
            } catch (Exception $exception) {
                if ($this->applicationState->getMode() === ApplicationState::MODE_PRODUCTION) {
                    throw $exception;
                }

                return $this->magewireUpdateResultFactory->create()->renderWith(
                    static function (HttpResponseInterface $response) use ($exception) {
                        $response->setBody($exception->getMessage());
                        $response->setHttpResponseCode(500);

                        return $response;
                    }
                );
            }

            return $this->magewireUpdateResultFactory->create()->renderWith(
                static function (HttpResponseInterface $response) use ($exception) {
                    $response->setBody('An unexpected error occurred while processing your request.');
                    $response->setHttpResponseCode(500);

                    return $response;
                }
            );
        }
    }

    /**
     * @throws LocalizedException
     */
    public function validateForCsrf(RequestInterface $request): bool|null
    {
        return Security::compareStrings($request->getParam('token'), $this->formKey->getFormKey());
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
