<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller;

use Exception;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magewirephp\Magento\App\Router\MagewireRouteValidator;
use Magewirephp\Magewire\MagewireServiceProvider;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class MagewireUpdateRoute extends MagewireRoute
{
    public const PARAM_IS_SUBSEQUENT = 'is_magewire_subsequent';
    public const PARAM_TOKEN = 'token';
    public const PARAM_COMPONENTS = 'components';

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ServiceInputProcessor $serviceInputProcessor,
        private readonly MagewireServiceProvider $magewireServiceProvider,
        private readonly ActionFactory $actionFactory,
        private readonly LoggerInterface $logger,
        private readonly MagewireRouteValidator $magewireRouteValidator
    ) {
        parent::__construct($this->actionFactory, $this->logger, $this->magewireRouteValidator);
    }

    public function match(RequestInterface $request): ActionInterface|null
    {
        $match = parent::match($request);

        if ($match === null) {
            return null;
        }

        // Mark the request as a subsequent Magewire request.
        $request->setParam(self::PARAM_IS_SUBSEQUENT, true);

        /**
         * Magewire has two trigger points for booting itself. One occurs during the updating of components.
         * This is the only feasible location, after confirming we are on an update request,
         * where we should attempt to boot. [2/2]
         *
         * @see \Magewirephp\Magewire\Observer\ViewBlockAbstractToHtmlBefore
         */
        $this->magewireServiceProvider->boot();

        try {
            $request->setParams($this->parseRequest($request));
        } catch (Exception $exception) {
            return $this->actionFactory->create(Forward::class);
        }

        return $match;
    }


    /**
     * @throws LocalizedException
     */
    protected function parseRequest(RequestInterface $request): array
    {
        // @todo: Consider finding a better way for retrieving request parameters.
        $input = $this->serializer->unserialize(file_get_contents('php://input'));

        foreach ($input[self::PARAM_COMPONENTS] as $key => $component) {
            $component['snapshot'] = $this->serializer->unserialize($component['snapshot']);

            $handle = $component['snapshot']['memo']['handle'] ?? null;
            $resolver = $component['snapshot']['memo']['resolver'] ?? null;

            /**
             * Magewire requires at least a component resolver accessor and any necessary layout update handles.
             * These layout handles must adhere to core Magento requirements for layout handles to proceed
             * with handling the update.
             *
             * @see Magento_Framework::View/Layout/etc/elements.xsd
             */
            if (! $resolver && (! $handle || preg_match('/^[a-zA-Z0-9][a-zA-Z\d\-_\.]*$/', $handle) !== 1)) {
                throw new RuntimeException('Base component prerequisites not satisfied.');
            }

            // Each component request context must conform to the service contract requirements.
            $input[self::PARAM_COMPONENTS][$key] = $this->serviceInputProcessor->convertValue($component, ComponentRequestContext::class);
        }

        /** @var Request $request */
        $request->setParam('token', $input['_token'] ?? null);

        unset($input['_token']);

        return array_merge($request->getParams(), $input);
    }
}
