<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Element;

class Redirect
{
    private string $url;
    private ?array $params;
    private bool $secure;

    /**
     * Redirect constructor.
     * @param string $path
     * @param array|null $params
     * @param bool $secure
     */
    public function __construct(
        string $path,
        ?array $params = null,
        bool $secure = true
    ) {
        $this->url = $path;
        $this->params = $params;
        $this->secure = $secure;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return ?array
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * @return bool
     */
    public function hasParams(): bool
    {
        return ! empty($this->params);
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }
}
