<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Serialize\SerializerInterface;

class Security
{
    protected DeploymentConfig $deployConfig;
    protected SerializerInterface $serializer;

    /**
     * Component constructor.
     * @param DeploymentConfig $deployConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        DeploymentConfig $deployConfig,
        SerializerInterface $serializer
    ) {
        $this->deployConfig = $deployConfig;
        $this->serializer = $serializer;
    }

    /**
     * @param array $data1
     * @param array $data2
     * @return string
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function generateChecksum(array $data1, array $data2): string
    {
        $key = $this->deployConfig->get('crypt/key');

        $hash = '' . $this->serializer->serialize($data1) . $this->serializer->serialize($data2);
        return hash_hmac('sha256', $hash, $key);
    }

    /**
     * @param string $checksum
     * @param array $data1
     * @param array $data2
     * @return bool
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function validateChecksum(string $checksum, array $data1, array $data2): bool
    {
        return hash_equals($this->generateChecksum($data1, $data2), $checksum);
    }
}
