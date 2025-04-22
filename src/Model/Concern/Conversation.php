<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

use Magento\Framework\Exception\LocalizedException;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

trait Conversation
{
    protected $request;
    protected $response;

    public function hasRequest(?string $section = null): bool
    {
        if (is_string($section)) {
            return $this->hasRequest() && is_array($this->request->getSectionByName($section));
        }

        return $this->request !== null;
    }

    /**
     * @return RequestInterface|array|null
     */
    public function getRequest(?string $section = null)
    {
        if (is_string($section)) {
            return $this->request->getSectionByName($section);
        }

        return $this->request;
    }

    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function hasResponse(?string $section = null): bool
    {
        if (is_string($section)) {
            try {
                return $this->hasResponse() && is_array($this->response->getSectionByName($section));
            } catch (LocalizedException $exception) {
                return false;
            }
        }

        return $this->response !== null;
    }

    /**
     * @return ResponseInterface|array
     */
    public function getResponse(?string $section = null)
    {
        if (is_string($section)) {
            return $this->response->getSectionByName($section);
        }

        return $this->response;
    }

    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;
        return $this;
    }
}
