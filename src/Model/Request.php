<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\Exception\LocalizedException;

class Request implements RequestInterface
{
    public $fingerprint;
    public $memo;
    public $meta;
    public $updates;

    protected bool $isSubsequent = false;
    protected bool $isRefreshing = false;

    public function getFingerprint(string $index = null)
    {
        if ($index !== null && is_array($this->fingerprint)) {
            return $this->fingerprint[$index] ?? null;
        }

        return $this->fingerprint;
    }

    public function setFingerprint($fingerprint): RequestInterface
    {
        $this->fingerprint = $fingerprint;
        return $this;
    }

    public function getServerMemo(string $index = null)
    {
        if ($index !== null && is_array($this->memo)) {
            return $this->memo[$index] ?? null;
        }

        return $this->memo;
    }

    public function setServerMemo($memo): RequestInterface
    {
        $this->memo = $memo;
        return $this;
    }

    public function getUpdates(string $index = null)
    {
        if ($index !== null && is_array($this->updates)) {
            return $this->updates[$index] ?? null;
        }

        return $this->updates;
    }

    public function setUpdates($updates): RequestInterface
    {
        $this->updates = $updates;
        return $this;
    }

    /**
     * @throws LocalizedException
     */
    public function getSectionByName(string $section): ?array
    {
        if (in_array($section, ['fingerprint', 'serverMemo', 'updates', 'dataMeta'])) {
            return $this->{$section};
        }

        throw new LocalizedException(__('Request section %s does not exist', $section));
    }

    public function isSubsequent(bool $flag = null, bool $force = false)
    {
        if ($flag === null) {
            return $this->isSubsequent;
        }

        // Lock this property, so it can't be changed later on.
        if ($force === true || $this->isSubsequent === false) {
            $this->isSubsequent = $flag;
        }

        return $this;
    }

    public function isPreceding(): bool
    {
        return ! $this->isSubsequent();
    }

    public function isRefreshing(bool $flag = null)
    {
        if ($flag === null) {
            return $this->isRefreshing;
        }

        $this->isRefreshing = $flag;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'fingerprint' => $this->getFingerprint(),
            'serverMemo'  => $this->getServerMemo(),
            'updates'     => $this->getUpdates(),
        ];
    }
}
