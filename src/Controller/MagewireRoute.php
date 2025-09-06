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
use Magewirephp\Magento\App\Router\MagewireRouteValidator;
use Psr\Log\LoggerInterface;

abstract class MagewireRoute
{
    public function __construct(
        private readonly ActionFactory $actionFactory,
        private readonly LoggerInterface $logger,
        private readonly MagewireRouteValidator $magewireRouteValidator
    ) {
        //
    }

    /**
     * Evaluates whether the given request satisfies all defined match conditions.
     *
     * Method iterates over the match conditions provided by `getMatchConditions()`. Each condition is a
     * closure that receives a `Request` object and returns a boolean indicating whether the request meets
     * the specific condition. If any condition fails or throws an exception, the method returns `false`.
     *
     * If all conditions pass, the method returns `true` and we can be sure this is a Magewire update request.
     */
    public function match(RequestInterface $request): ActionInterface|null
    {
        $conditions = $this->getMatchConditions();

        /*
         * Magewire routes must always start with the Magewire base route.
         * Although this is already validated during the normal routing process,
         * this object can be used independently, so the route must always be revalidated here before proceeding.
         */
        array_unshift($conditions, fn () => $this->magewireRouteValidator->validate($request));

        foreach ($conditions as $name => $condition) {
            try {
                if (! $condition($request)) {
                    return null;
                }
            } catch (Exception $exception) {
                $this->log()->debug(
                    sprintf('Route match condition "%s" threw an exception', $name),
                    ['exception' => $exception]
                );

                return null;
            }
        }

        return $this->createAction($request);
    }

    /**
     * Returns an array of match conditions as closures to validate certain aspects of a request.
     *
     * Each condition is a closure that takes a `Request` object as a parameter and returns a boolean value
     * indicating whether the request satisfies the specific condition. These conditions could be used to
     * filter or validate incoming HTTP requests based on certain criteria like request method, URI, and content type.
     *
     * TODO: Currently, conditions are a flat array of callables, each of which must return true for the check to pass.
     *       If any callable returns false, the entire check fails. Future plans include supporting nested arrays,
     *       where each sub-array acts as an OR condition—if any callable in a sub-array returns true,
     *       that group is considered satisfied.
     *
     * @return array<string, callable>
     */
    abstract public function getMatchConditions(): array;

    abstract public function createAction(RequestInterface $request): ActionInterface;

    public function actionFactory(): ActionFactory
    {
        return $this->actionFactory;
    }

    public function log(): LoggerInterface
    {
        return $this->logger;
    }
}
