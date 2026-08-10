<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\Exceptions;

use Magento\Framework\App\State as ApplicationState;
use Magewirephp\Magewire\Mechanisms\HandleRequests\Filter\RequestFilterInterface;
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestContext;
use Magewirephp\Magewire\Support\Enum\MessageType;
use Throwable;

/**
 * Rejects an update on demand, so the Playwright suite can exercise the real server path rather
 * than a stubbed response: pipeline, exception handler, status, message and severity header.
 *
 * Deliberately reads nothing but the parsed envelope, which is all a filter has at this point.
 * Reaching a decision from the pending method call alone is itself the proof that filters run
 * before reconstruction.
 *
 * Only ever acts on the Playwright exceptions page, and never outside developer mode, matching the
 * gate already applied to the page itself.
 *
 * @see \Magewirephp\Magewire\Controller\MagewireDeveloperAction
 */
class PlaywrightRejectionFilter implements RequestFilterInterface
{
    private const COMPONENT_PREFIX = 'magewire.playwright.exceptions';

    /**
     * Method name to the status and severity its rejection answers with.
     *
     * Every status sits outside the 2xx range on purpose, success included: the frontend hook only
     * sees responses the browser treats as failures, so a 2xx would never reach it.
     */
    private const REJECTIONS = [
        'rejectWarning' => [429, MessageType::WARNING],
        'rejectError' => [403, MessageType::ERROR],
        'rejectInfo' => [503, MessageType::INFO],
        'rejectSuccess' => [409, MessageType::SUCCESS],
    ];

    public function __construct(
        private readonly ApplicationState $applicationState
    ) {
    }

    public function check(RequestContext $context): void
    {
        if ($this->isProduction()) {
            return;
        }

        foreach ($context->getComponents() as $componentRequestContext) {
            $id = (string) $componentRequestContext->getSnapshot()->getMemoValue('id');

            if (! str_starts_with($id, self::COMPONENT_PREFIX)) {
                continue;
            }

            foreach ($componentRequestContext->getCalls() as $call) {
                $rejection = self::REJECTIONS[$call['method'] ?? ''] ?? null;

                if ($rejection === null) {
                    continue;
                }

                [$status, $severity] = $rejection;

                throw new PlaywrightRejectionException(
                    $status,
                    $severity,
                    sprintf('Rejected by the Playwright filter with a %s severity.', $severity->type())
                );
            }
        }
    }

    /**
     * Fails towards production, so an unreadable mode keeps this test-only filter dormant.
     */
    private function isProduction(): bool
    {
        try {
            return $this->applicationState->getMode() === ApplicationState::MODE_PRODUCTION;
        } catch (Throwable) {
            return true;
        }
    }
}
