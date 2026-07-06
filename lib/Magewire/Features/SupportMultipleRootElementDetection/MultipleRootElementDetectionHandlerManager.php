<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection;

use Magento\Framework\App\State as ApplicationState;
use Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Api\MultipleRootElementDetectionHandlerInterface;
use Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Config\Source\DetectionBehavior;
use Throwable;

/**
 * Selects the configured detection behavior from a DI-registered pool and delegates to it.
 * Unknown/missing keys fall back to the default handler. The pool is the extension point:
 * third parties register additional handlers keyed by their behavior value.
 *
 * @api
 */
class MultipleRootElementDetectionHandlerManager
{
    /**
     * @param array<string, MultipleRootElementDetectionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly MultipleRootElementDetectionConfig $config,
        private readonly MultipleRootElementDetectionHandlerInterface $defaultHandler,
        private readonly ApplicationState $appState,
        private readonly array $handlers = []
    ) {
    }

    /**
     * Whether detection should run at all. It is a development aid, so it is skipped in
     * production, and when explicitly turned "off". When disabled, callers skip the HTML
     * parse entirely.
     */
    public function isEnabled(): bool
    {
        if ($this->getAppMode() === ApplicationState::MODE_PRODUCTION) {
            return false;
        }

        return $this->config->getBehavior() !== DetectionBehavior::OFF;
    }

    public function handle(object $component, string $html, int $rootCount): string
    {
        return $this->resolve()->handle($component, $html, $rootCount);
    }

    private function resolve(): MultipleRootElementDetectionHandlerInterface
    {
        $handler = $this->handlers[$this->config->getBehavior()] ?? null;

        return $handler instanceof MultipleRootElementDetectionHandlerInterface ? $handler : $this->defaultHandler;
    }

    private function getAppMode(): string
    {
        try {
            return $this->appState->getMode();
        } catch (Throwable $exception) {
            return ApplicationState::MODE_DEVELOPER;
        }
    }
}
