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
    public $updates;

    protected bool $isSubsequent = false;

    /**
     * @inheritdoc
     */
    public function getFingerprint(string $index = null)
    {
        if ($index !== null && is_array($this->fingerprint)) {
            return $this->fingerprint[$index] ?? null;
        }

        return $this->fingerprint;
    }

    /**
     * @inheritdoc
     */
    public function setFingerprint($fingerprint): RequestInterface
    {
        $this->fingerprint = $fingerprint;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getServerMemo(string $index = null)
    {
        if ($index !== null && is_array($this->memo)) {
            return $this->memo[$index] ?? null;
        }

        return $this->memo;
    }

    /**
     * @inheritdoc
     */
    public function setServerMemo($memo): RequestInterface
    {
        $this->memo = $memo;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdates(string $index = null)
    {
        if ($index !== null && is_array($this->updates)) {
            return $this->updates[$index] ?? null;
        }

        return $this->updates;
    }

    /**
     * @inheritdoc
     */
    public function setUpdates($updates): RequestInterface
    {
        $this->updates = $updates;
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    public function getSectionByName(string $section): ?array
    {
        if (in_array($section, ['fingerprint', 'serverMemo', 'updates'])) {
            return $this->{$section};
        }

        throw new LocalizedException(__('Request section %s does not exist', $section));
    }

    /**
     * @inheritdoc
     */
    public function isSubsequent(bool $flag = null)
    {
        // Just return the update status.
        if ($flag === null) {
            return $this->isSubsequent;
        }

        // Lock this property, so it can't be changed later on.
        if ($this->isSubsequent === false) {
            $this->isSubsequent = $flag;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isPreceding(): bool
    {
        return !$this->isSubsequent();
    }
}
