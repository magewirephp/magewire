<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\Controller;

use Closure;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use function Magewirephp\Magewire\trigger;

class MagewireUpdateResult extends AbstractResult
{
    private Closure|null $renderer = null;
    private bool $rendered = false;

    public function __construct(
        private readonly JsonSerializer $jsonSerializer,
        private readonly array $components = [],
        private readonly array $assets = []
    ) {
        //
    }

    public function renderWith(Closure $renderer): self
    {
        if ($this->rendered) {
            return $this;
        }

        $this->renderer = $renderer;

        return $this;
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function render(HttpResponseInterface $response): self
    {
        $this->renderer ??= function (HttpResponseInterface $response, MagewireUpdateResult $result): HttpResponseInterface {
            return $response->setBody(
                $this->jsonSerializer->serialize([
                    'components' => $result->getComponents(),
                    'assets' => $result->getAssets()
                ])
            );
        };

        call_user_func($this->renderer, $response, $this);
        $this->rendered = true;

        trigger('magewire:response.render', $response);

        $response->setHeader('Content-Type', 'application/json', true);
        $response->setHeader('X-Built-With', 'Magewire', true);

        return $this;
    }
}
