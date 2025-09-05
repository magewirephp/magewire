<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\App;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Controller\MagewireUpdateRoute;
use Magewirephp\Magewire\Exceptions\SilentException;
use Psr\Log\LoggerInterface;

/**
 * The exception handler provides developers with greater control over actions to take when an exception occurs,
 * whether during a preceding page render or a subsequent component update attempt. This manager enables binding
 * specific exception types to either a new exception or throwing a replica with different arguments.
 *
 * @api
 */
class ExceptionManager
{
    /**
     * @param array<Exception::class,'subsequent'|'predecing', AbstractExceptionHandler|array<Exception::class, AbstractExceptionHandler>> $specificExceptionHandlerPool
     */
    function __construct(
        private readonly RequestInterface $request,
        private readonly LoggerInterface $logger,
        private readonly AbstractExceptionHandler $precedingHandler,
        private readonly AbstractExceptionHandler $subsequentHandler,
        private readonly array $specificExceptionHandlerPool = []
    ) {
        //
    }

    /**
     * @throws Exception
     */
    function handle(Exception $exception, bool $log = true): callable|null
    {
        $subsequent = $this->request->getParam(MagewireUpdateRoute::PARAM_IS_SUBSEQUENT, false);

        try {
            $exception = $this->resolveExceptionHandler($exception, $subsequent)->handle($exception, $subsequent);
        } catch (Exception $parent) {
            $exception = $parent;
        }

        if ($exception instanceof SilentException) {
            return null;
        } elseif (is_callable($exception)) {
            return $exception;
        }

        if ($log) {
            $this->logger->info(sprintf('Magewire: %s', $exception->getMessage()), ['exception' => $exception]);
        }

        throw $exception;
    }

    /**
     * @throws Exception
     */
    function handleWithBlock(AbstractBlock $block, Exception $exception, bool $log = true): AbstractBlock
    {
        $subsequent = $this->request->getParam(MagewireUpdateRoute::PARAM_IS_SUBSEQUENT, false);

        if ($subsequent) {
            $this->handle($exception, $log);
        }

        // Prevent cyclic loops from re-triggering the entire Magewire lifecycle.
        $block->unsetData('magewire');
        // Making sure the exception is always present on the block.
        $block->setData('exception', $exception);

        try {
            $block = $this->resolveExceptionHandler($exception, $subsequent)
                          ->handleWithBlock($block, $exception, $subsequent);
        } catch (Exception $parent) {
            $this->handle(
                new SilentException(
                    $parent->getMessage(),
                    $parent->getCode(),
                    $parent->getPrevious()
                )
            );
        }

        if ($log) {
            $this->logger->info($exception->getMessage(), ['exception' => $exception]);
        }

        return $block;
    }

    private function resolveExceptionHandler(Exception $exception, bool $subsequent = false): AbstractExceptionHandler
    {
        $exceptionGroup = $subsequent ? 'subsequent' : 'preceding';
        $exceptionClass = $exception::class;
        $exceptionHandlerPool = $this->specificExceptionHandlerPool;

        // Check for a general subsequent tor preceding handler.
        if (is_object($exceptionHandlerPool[$exceptionGroup] ?? null)) {
            $handler = $this->specificExceptionHandlerPool[$exceptionClass]
                ?? $exceptionHandlerPool[$exceptionGroup];

            if ($handler instanceof AbstractExceptionHandler) {
                return $handler;
            }

            // Enable to continue the search by switching the group into an array.
            $exceptionHandlerPool[$exceptionGroup] = [];
        }

        $fallback = $subsequent ? $this->subsequentHandler : $this->precedingHandler;

        // Check for a grouped handler for the specific exception class.
        $handler = $exceptionHandlerPool[$exceptionGroup][$exceptionClass] ?? null
            ? $exceptionHandlerPool[$exceptionGroup][$exceptionClass]

            // Check for a handler for the specific exception class.
            : ($exceptionHandlerPool[$exceptionClass] ?? null
                ? $exceptionHandlerPool[$exceptionClass]

                // Continue with the fallback if nothing specific was found.
                : $fallback);

        if (! $handler instanceof AbstractExceptionHandler) {
            return $fallback;
        }

        return $handler;
    }
}
