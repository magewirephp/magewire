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

    public function __construct(
        string $path,
        ?array $params = null,
        bool $secure = true
    ) {
        $this->url = $path;
        $this->params = $params;
        $this->secure = $secure;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function hasParams(): bool
    {
        return ! empty($this->params);
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }
}
