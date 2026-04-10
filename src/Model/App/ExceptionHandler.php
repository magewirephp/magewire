<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\App;

use Exception;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\View\Element\AbstractBlock;

class ExceptionHandler extends AbstractExceptionHandler
{
    function __construct(
        private readonly ApplicationState $state
    ) {
    }

    function handle(Exception $exception, bool $subsequent = false): Exception|callable
    {
        if (! $subsequent) {
            return parent::handle($exception, $subsequent);
        }

        if ($this->state->getMode() === ApplicationState::MODE_DEVELOPER) {
            return static function (HttpResponseInterface $response) use ($exception): HttpResponseInterface {
                $original = $exception;

                while ($original->getPrevious() !== null) {
                    $original = $original->getPrevious();
                }

                $data = [
                    'magewire_error' => true,
                    'type' => $original::class,
                    'message' => $original->getMessage(),
                    'code' => $original->getCode(),
                    'file' => $original->getFile(),
                    'line' => $original->getLine(),
                    'trace' => array_map(static function (array $frame): array {
                        return [
                            'file' => $frame['file'] ?? null,
                            'line' => $frame['line'] ?? null,
                            'class' => $frame['class'] ?? null,
                            'function' => $frame['function'] ?? null,
                        ];
                    }, array_slice($original->getTrace(), 0, 30)),
                ];

                $response->setBody(json_encode($data, JSON_UNESCAPED_SLASHES));
                $response->setHttpResponseCode(500);

                return $response;
            };
        }

        return static function (HttpResponseInterface $response): HttpResponseInterface {
            $data = [
                'magewire_error' => true,
                'message' => 'An unexpected error occurred while processing your request.',
            ];

            $response->setBody(json_encode($data, JSON_UNESCAPED_SLASHES));
            $response->setHttpResponseCode(500);

            return $response;
        };
    }

    function handleWithBlock(AbstractBlock $block, Exception $exception, bool $subsequent = false): AbstractBlock
    {
        $block = parent::handleWithBlock($block, $exception, $subsequent);
        $block->setData('application_state', $this->state);

        return $block;
    }
}
