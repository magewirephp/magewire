<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller\Playwright;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magewirephp\Magewire\Controller\MagewireDeveloperAction;

class Resolvers extends MagewireDeveloperAction implements HttpGetActionInterface
{
    protected string $pageTitle = 'Magewire / Playwright / Resolvers';
}
