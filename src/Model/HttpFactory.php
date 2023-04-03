<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\ServiceInputProcessor;

class HttpFactory
{
    protected ServiceInputProcessor $serviceInputProcessor;

    public function __construct(
        ServiceInputProcessor $serviceInputProcessor
    ) {
        $this->serviceInputProcessor = $serviceInputProcessor;
    }

    /**
     * @throws LocalizedException
     */
    public function createRequest(array $data): RequestInterface
    {
        return $this->serviceInputProcessor->convertValue($data, RequestInterface::class);
    }

    /**
     * @throws LocalizedException
     */
    public function createResponse(RequestInterface $request, array $data = []): ResponseInterface
    {
        return $this->serviceInputProcessor->convertValue(array_merge([
            'request' => $request,
            'fingerprint' => $request->getFingerprint(),
            'serverMemo' => $request->getServerMemo(),
            'effects' => []
        ], $data), ResponseInterface::class);
    }
}
