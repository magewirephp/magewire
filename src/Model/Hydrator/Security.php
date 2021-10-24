<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\CorruptPayloadException;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

/**
 * Class Security.
 */
class Security implements HydratorInterface
{
    /** @var SecurityHelper */
    private $securityHelper;

    /**
     * Security constructor.
     *
     * @param SecurityHelper $securityHelper
     */
    public function __construct(
        SecurityHelper $securityHelper
    ) {
        $this->securityHelper = $securityHelper;
    }

    /**
     * @inheritdoc
     *
     * @param Component        $component
     * @param RequestInterface $request
     *
     * @throws CorruptPayloadException
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if (!isset($request->memo['checksum'])) {
            return;
        }

        $checksum = $request->memo['checksum'];
        unset($request->memo['checksum']);

        $memo = array_diff_key($request->getServerMemo(), array_flip(['children']));

        if (!$this->securityHelper->validateChecksum($checksum, $request->getFingerprint(), $memo)) {
            throw new CorruptPayloadException('Temporary Component Name (todo)');
        }
    }

    /**
     * @inheritdoc
     *
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        $response->memo['checksum'] = $this->securityHelper->generateChecksum($response->getFingerprint(), $response->getServerMemo());
    }
}
