<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Element;

/**
 * Class Redirect.
 */
class Redirect
{
    protected $url;
    protected $params = [];

    /**
     * Redirect constructor.
     *
     * @param string $path
     * @param array  $params
     */
    public function __construct(
        string $path,
        array $params = []
    ) {
        $this->url = $path;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return bool
     */
    public function hasParams(): bool
    {
        return !empty($this->params);
    }
}
