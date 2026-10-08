<?php

declare(strict_types=1);

namespace Magewirephp\MagewirePlaywright\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Magewirephp\MagewirePlaywright\Model\Failure;

class RenderFailure
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Failure $failure
    ) {
    }

    public function afterRenderResult(Page $subject, Page $result): Page
    {
        if ($this->request->getRouteName() === 'magewire_fixture') {
            // Fail after the layout renders, outside Layout's production Exception catch.
            $this->failure->raise((string) $this->request->getParam('failure', ''));
        }

        return $result;
    }
}
