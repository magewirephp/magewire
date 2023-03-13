<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey as ApplicationFormKey;
use Magento\Framework\Encryption\Helper\Security as EncryptionSecurityHelper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Serialize\SerializerInterface;

class Security
{
    protected DeploymentConfig $deployConfig;
    protected SerializerInterface $serializer;
    protected ApplicationFormKey $formkey;

    public function __construct(
        DeploymentConfig $deployConfig,
        SerializerInterface $serializer,
        ApplicationFormKey $formkey
    ) {
        $this->deployConfig = $deployConfig;
        $this->serializer = $serializer;
        $this->formkey = $formkey;
    }

    /**
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
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function validateChecksum(string $checksum, array $data1, array $data2): bool
    {
        return hash_equals($this->generateChecksum($data1, $data2), $checksum);
    }

    /**
     * @throws LocalizedException
     */
    public function validateFormKey(RequestInterface $request): bool
    {
        return EncryptionSecurityHelper::compareStrings($request->getHeader('X-CSRF-TOKEN'), $this->formkey->getFormKey());
    }
}
