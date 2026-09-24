<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller\Playwright;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magewirephp\Magewire\Controller\MagewireDeveloperAction;

class ComponentLoader extends MagewireDeveloperAction implements HttpGetActionInterface
{
    protected string $pageTitle = 'Magewire / Playwright / Component Loader';
}
