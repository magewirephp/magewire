<?php declare(strict_types=1);

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magewirephp\Magewire\MagewireServiceProvider;

/**
 * @deprecated
 */
class Request implements RequestInterface
{
    public function __construct(
        private readonly \Magento\Framework\App\RequestInterface $request,
        private readonly MagewireServiceProvider $magewireServiceProvider
    ) {
    }

    public function getMessage(): string
    {
        // TODO: Implement getMessage() method.
    }

    public function setMessage(string $message): \Magewirephp\Magewire\Model\RequestInterface
    {
        return $this;
    }

    public function getFingerprint(string $index)
    {
        // TODO: Implement getFingerprint() method.
    }

    public function setFingerprint($fingerprint): \Magewirephp\Magewire\Model\RequestInterface
    {
        return $this;
    }

    public function getServerMemo(string $index)
    {
        // TODO: Implement getServerMemo() method.
    }

    public function setServerMemo($memo): \Magewirephp\Magewire\Model\RequestInterface
    {
        return $this;
    }

    public function getUpdates(string $index)
    {
        // TODO: Implement getUpdates() method.
    }

    public function setUpdates($updates): \Magewirephp\Magewire\Model\RequestInterface
    {
        return $this;
    }

    public function getSectionByName(string $section): array|null
    {
        return null;
    }

    public function isSubsequent(bool $flag = null, bool $force = false)
    {
        return $this->magewireServiceProvider
            ->runtime()
            ->mode()
            ->isSubsequent();
    }

    public function isPreceding(): bool
    {
        return ! $this->isSubsequent();
    }

    public function isRefreshing(bool $flag = null)
    {
        return false;
    }

    public function toArray(): array
    {
        return [];
    }
}
