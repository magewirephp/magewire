<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests;

use Magento\Framework\App\Request\Http;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Session\SessionManagerInterface;
use Throwable;

/**
 * Resolves a stable, opaque identifier for the origin of an update request.
 *
 * Prefers the session identifier since it survives IP changes within a single browsing session,
 * and falls back to the remote address when no session is available. The result is hashed so it
 * can be used inside cache keys without exposing the session identifier itself.
 *
 * @api
 */
class RequestFingerprint
{
    private string|null $fingerprint = null;

    public function __construct(
        private readonly Http $request,
        private readonly SessionManagerInterface $session,
        private readonly RemoteAddress $remoteAddress
    ) {
    }

    public function resolve(): string
    {
        return $this->fingerprint ??= hash('sha256', implode('|', [
            $this->resolveOrigin(),
            (string) $this->request->getServer('HTTP_USER_AGENT', ''),
        ]));
    }

    private function resolveOrigin(): string
    {
        try {
            $sessionId = $this->session->getSessionId();
        } catch (Throwable) {
            $sessionId = null;
        }

        if (is_string($sessionId) && $sessionId !== '') {
            return $sessionId;
        }

        return (string) ( $this->remoteAddress->getRemoteAddress() ?: 'unknown' );
    }
}
