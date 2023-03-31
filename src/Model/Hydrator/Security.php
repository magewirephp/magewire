<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magewire\Exception\CorruptPayloadException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class Security implements HydratorInterface
{
    protected DeploymentConfig $deployConfig;
    protected SerializerInterface $serializer;

    public function __construct(
        DeploymentConfig $deployConfig,
        SerializerInterface $serializer
    ) {
        $this->deployConfig = $deployConfig;
        $this->serializer = $serializer;
    }

    /**
     * @throws FileSystemException
     * @throws CorruptPayloadException
     * @throws RuntimeException
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if (! isset($request->memo['checksum'])) {
            return;
        }

        $checksum = $request->memo['checksum'];
        unset($request->memo['checksum']);

        $memo = array_diff_key($request->getServerMemo(), array_flip(['children']));

        if (! $this->validateChecksum($checksum, $request->getFingerprint(), $memo)) {
            throw new CorruptPayloadException(get_class($component));
        }
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        $memo = array_diff_key($response->getServerMemo(), array_flip(['children']));
        $response->memo['checksum'] = $this->generateChecksum($response->getFingerprint(), $memo);
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    protected function generateChecksum(array $data1, array $data2): string
    {
        $key = $this->deployConfig->get('crypt/key');

        $hash = '' . $this->serializer->serialize($data1) . $this->serializer->serialize($data2);
        return hash_hmac('sha256', $hash, $key);
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    protected function validateChecksum(string $checksum, array $data1, array $data2): bool
    {
        return hash_equals($this->generateChecksum($data1, $data2), $checksum);
    }
}
