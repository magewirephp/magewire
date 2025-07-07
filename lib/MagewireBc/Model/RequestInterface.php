<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

/**
 * @deprecated
 */
interface RequestInterface
{
    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @param $fingerprint
     * @return \Magewirephp\Magewire\Model\RequestInterface
     */
    public function setMessage(string $message): \Magewirephp\Magewire\Model\RequestInterface;

    /**
     * string $index
     * @return mixed
     */
    public function getFingerprint(string $index);

    /**
     * @param $fingerprint
     * @return \Magewirephp\Magewire\Model\RequestInterface
     */
    public function setFingerprint($fingerprint): \Magewirephp\Magewire\Model\RequestInterface;

    /**
     * string $index
     * @return mixed
     */
    public function getServerMemo(string $index);

    /**
     * @param $memo
     * @return \Magewirephp\Magewire\Model\RequestInterface
     */
    public function setServerMemo($memo): \Magewirephp\Magewire\Model\RequestInterface;

    /**
     * string $index
     * @return mixed
     */
    public function getUpdates(string $index);

    /**
     * @param $updates
     * @return \Magewirephp\Magewire\Model\RequestInterface
     */
    public function setUpdates($updates): \Magewirephp\Magewire\Model\RequestInterface;

    /**
     * @param string $section
     * @return array|null
     */
    public function getSectionByName(string $section): ?array;

    /**
     * Check if request is an component update request.
     *
     * @param bool|null $flag
     * @param bool $force
     * @return \Magewirephp\Magewire\Model\RequestInterface|bool
     */
    public function isSubsequent(?bool $flag = null, bool $force = false);

    /**
     * Check if on a component initialization request.
     *
     * @return bool
     */
    public function isPreceding(): bool;

    /**
     * Check if request is on a refresh update event.
     *
     * @param bool|null $flag
     * @return \Magewirephp\Magewire\Model\RequestInterface|bool
     */
    public function isRefreshing(?bool $flag = null);

    /**
     * @return array
     */
    public function toArray(): array;
}
