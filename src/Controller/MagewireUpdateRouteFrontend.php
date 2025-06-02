<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magewirephp\Magewire\Controller\Magewire\Update;

class MagewireUpdateRouteFrontend extends MagewireUpdateRoute
{
    public function getMatchConditions(): array
    {
        return [
            'method' => fn (Request $request): bool => $request->isPost(),
            'update_uri' => fn (Request $request): bool => str_starts_with($request->getPathInfo(), '/magewire/update'),
            'content_type' => fn (Request $request): bool => $request->getHeader('Content-Type') === 'application/json'
        ];
    }

    public function createAction(RequestInterface $request): ActionInterface
    {
        return $this->actionFactory()->create(Update::class);
    }
}
