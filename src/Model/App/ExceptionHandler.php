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
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Exceptions\MethodNotFoundException;

class ExceptionHandler extends AbstractExceptionHandler
{
    function __construct(
        private readonly ApplicationState $state,
        private readonly DirectoryList $directoryList
    ) {
    }

    function handle(Exception $exception, bool $subsequent = false): Exception|callable
    {
        if (! $subsequent) {
            return parent::handle($exception, $subsequent);
        }

        // PHP warnings converted by Magento's ErrorHandler are not developer-meaningful
        // in the context of the error panel — let the parent wrap them as RequestException
        // so processing continues and the real exception (e.g. MethodNotFoundException) surfaces.
        if ($exception instanceof \ErrorException) {
            return parent::handle($exception, $subsequent);
        }

        if ($this->state->getMode() === ApplicationState::MODE_DEVELOPER) {
            $root = rtrim($this->directoryList->getRoot(), '/') . '/';

            return static function (HttpResponseInterface $response) use ($exception, $root): HttpResponseInterface {
                $original = $exception;

                while ($original->getPrevious() !== null) {
                    $original = $original->getPrevious();
                }

                [$file, $line] = self::resolveLocation($exception, $original);

                $data = [
                    'magewire_error' => true,
                    'type' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => self::stripRoot($file, $root),
                    'line' => $line,
                    'trace' => array_map(static function (array $frame) use ($root): array {
                        return [
                            'file' => isset($frame['file']) ? self::stripRoot($frame['file'], $root) : null,
                            'line' => $frame['line'] ?? null,
                            'class' => $frame['class'] ?? null,
                            'function' => $frame['function'] ?? null,
                        ];
                    }, array_slice($original->getTrace(), 0, 30)),
                ];

                if ($exception->getCode() !== 0) {
                    $data['code'] = $exception->getCode();
                }

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

    private static function resolveLocation(Exception $exception, Exception $original): array
    {
        // For MethodNotFoundException, the exception is thrown inside Magewire internals.
        // Use the component file (resolved at throw time) as the primary location reference
        // so the panel points the developer to where the method should be defined.
        if ($exception instanceof MethodNotFoundException && $exception->getComponentFile() !== '') {
            return [$exception->getComponentFile(), $exception->getComponentLine()];
        }

        return [$original->getFile(), $original->getLine()];
    }

    private static function stripRoot(?string $path, string $root): ?string
    {
        if ($path === null) {
            return null;
        }

        if (str_starts_with($path, $root)) {
            return substr($path, strlen($root));
        }

        return $path;
    }

    function handleWithBlock(AbstractBlock $block, Exception $exception, bool $subsequent = false): AbstractBlock
    {
        $block = parent::handleWithBlock($block, $exception, $subsequent);
        $block->setData('application_state', $this->state);

        return $block;
    }
}
