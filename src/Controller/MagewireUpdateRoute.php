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
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magento\App\Router\MagewireRouteValidator;
use Magewirephp\Magewire\Enums\RequestMode;
use Magewirephp\Magewire\MagewireServiceProvider;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Checksum;
use Magewirephp\Magewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use Magewirephp\Magewire\Mechanisms\HandleComponents\SnapshotFactory;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContextFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function Magewirephp\Magewire\trigger;

/**
 * Handles routing for Magewire component update requests.
 */
abstract class MagewireUpdateRoute extends MagewireRoute
{
    public const PARAM_IS_SUBSEQUENT = 'is_magewire_subsequent';
    public const PARAM_TOKEN = 'token';
    public const PARAM_COMPONENTS = 'components';

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly SnapshotFactory $snapshotFactory,
        private readonly ComponentRequestContextFactory $componentRequestContextFactory,
        private readonly MagewireServiceProvider $magewireServiceProvider,
        private readonly ActionFactory $actionFactory,
        private readonly LoggerInterface $logger,
        private readonly MagewireRouteValidator $magewireRouteValidator,
        private readonly Checksum $checksum
    ) {
        parent::__construct($this->actionFactory, $this->logger, $this->magewireRouteValidator);
    }

    /**
     * Matches and processes Magewire update requests.
     *
     * Validates the request, boots Magewire in subsequent mode, and parses
     * component data. Returns a forward action on failure or the matched
     * action on success.
     */
    public function match(RequestInterface $request): ActionInterface|null
    {
        $match = parent::match($request);

        if ($match === null) {
            return null;
        }

        /**
         * Boot Magewire and initialize the request context.
         *
         * Magewire has two trigger points for initialization:
         * 1. During component updates (the only feasible location after confirming an update request)
         * 2. During page load via the view block observer
         *
         * This call marks the request as "subsequent" to distinguish between initial page loads
         * and subsequent update requests, allowing system-wide determination of Magewire's state.
         *
         * @see \Magewirephp\Magewire\Observer\ViewBlockAbstractToHtmlBefore
         */
        $this->magewireServiceProvider->boot(RequestMode::SUBSEQUENT);

        try {
            $request->setParams($this->parseRequest($request));
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            return null;
        }

        return $match;
    }

    /**
     * Parses and validates the component update request payload.
     *
     * Deserializes component data from the request body, verifies checksums,
     * validates component prerequisites (resolver/handle), and converts
     * component data to service contract objects.
     *
     * @throws LocalizedException
     * @throws FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     * @throws CorruptComponentPayloadException
     */
    protected function parseRequest(RequestInterface $request): array
    {
        // @todo: Consider finding a better way for retrieving request parameters.
        $input = $this->serializer->unserialize(file_get_contents('php://input'));

        foreach ($input[self::PARAM_COMPONENTS] as $key => $component) {
            $component['snapshot'] = $this->serializer->unserialize($component['snapshot']);

            $this->checksum->verify($component['snapshot']);
            trigger('snapshot-verified', $component['snapshot']);

            $handle = $component['snapshot']['memo']['handle'] ?? null;
            $resolver = $component['snapshot']['memo']['resolver'] ?? null;

            /**
             * Magewire requires at least a component resolver accessor and any necessary layout update handles.
             * These layout handles must adhere to core Magento requirements for layout handles to proceed
             * with handling the update.
             *
             * @see Magento_Framework::View/Layout/etc/elements.xsd
             */
            if (! $resolver && (! $handle || preg_match('/^[a-zA-Z0-9][a-zA-Z\d\-_\.]*$/', $handle) !== 1 )) {
                throw new RuntimeException('Base component prerequisites not satisfied.');
            }

            $snapshot = $this->snapshotFactory->create([
                'data'     => $component['snapshot']['data'] ?? [],
                'memo'     => $component['snapshot']['memo'] ?? [],
                'checksum' => $component['snapshot']['checksum'] ?? '',
            ]);

            $input[self::PARAM_COMPONENTS][$key] = $this->componentRequestContextFactory->create([
                'snapshot' => $snapshot,
                'calls'    => $component['calls'] ?? [],
                'updates'  => $component['updates'] ?? [],
            ]);
        }

        /** @var Request $request */
        $request->setParam('token', $input['_token'] ?? null);

        unset($input['_token']);

        return array_merge($request->getParams(), $input);
    }
}
