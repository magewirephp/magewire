<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\ServiceInputProcessor;

/**
 * Class HttpFactory.
 */
class HttpFactory
{
    /** @var ServiceInputProcessor */
    private $serviceInputProcessor;

    /**
     * HttpFactory constructor.
     *
     * @param ServiceInputProcessor $serviceInputProcessor
     */
    public function __construct(
        ServiceInputProcessor $serviceInputProcessor
    ) {
        $this->serviceInputProcessor = $serviceInputProcessor;
    }

    /**
     * @param array $data
     *
     * @throws LocalizedException
     *
     * @return RequestInterface
     */
    public function createRequest(array $data): RequestInterface
    {
        return $this->serviceInputProcessor->convertValue($data, RequestInterface::class);
    }

    /**
     * @param RequestInterface $request
     * @param array            $data
     *
     * @throws LocalizedException
     *
     * @return ResponseInterface
     */
    public function createResponse(RequestInterface $request, array $data = []): ResponseInterface
    {
        return $this->serviceInputProcessor->convertValue(array_merge([
            'request'     => $request,
            'fingerprint' => $request->getFingerprint(),
            'serverMemo'  => $request->getServerMemo(),
            'effects'     => [],
        ], $data), ResponseInterface::class);
    }
}
