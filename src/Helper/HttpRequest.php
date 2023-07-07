<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\App\RequestInterface;

class HttpRequest
{
    private RequestInterface $request;

    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    public function isMagewireRequest(): bool
    {
        return (
            $this->request->isPost() &&
            substr_count($this->request->getRequestUri(), 'magewire/post/livewire') > 0
        );
    }
}
