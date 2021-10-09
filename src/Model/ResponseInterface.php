<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

/**
 * Interface ResponseInterface
 * @package Magewirephp\Magewire\Model
 *
 * @api
 */
interface ResponseInterface
{
    /**
     * @return mixed
     */
    public function getRequest();

    /**
     * @param $request
     * @return \Magewirephp\Magewire\Model\ResponseInterface
     */
    public function setRequest($request): \Magewirephp\Magewire\Model\ResponseInterface;

    /**
     * @return mixed
     */
    public function getFingerprint();

    /**
     * @param $fingerprint
     * @return \Magewirephp\Magewire\Model\ResponseInterface
     */
    public function setFingerprint($fingerprint): \Magewirephp\Magewire\Model\ResponseInterface;

    /**
     * @return mixed
     */
    public function getServerMemo();

    /**
     * @param $memo
     * @return \Magewirephp\Magewire\Model\ResponseInterface
     */
    public function setServerMemo($memo): \Magewirephp\Magewire\Model\ResponseInterface;

    /**
     * @return mixed
     */
    public function getEffects();

    /**
     * @param $effects
     * @return \Magewirephp\Magewire\Model\ResponseInterface
     */
    public function setEffects($effects): \Magewirephp\Magewire\Model\ResponseInterface;

    /**
     * Renders the effects html with additional root attribute(s).
     *
     * @param array $data
     * @param bool $secure
     * @return string
     */
    public function renderWithRootAttribute(array $data, bool $secure = false): string;
}
