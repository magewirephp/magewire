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
     * @param string $section
     * @return array|null
     */
    public function getSectionByName(string $section): ?array;

    /**
     * Renders the effects html with additional root attribute(s).
     *
     * @param array $data
     * @param bool $includeBody
     * @return string
     */
    public function renderWithRootAttribute(array $data, bool $includeBody = true): string;
}
