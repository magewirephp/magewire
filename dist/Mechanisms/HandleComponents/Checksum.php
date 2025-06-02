<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Mechanisms\HandleComponents;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use function Magewirephp\Magewire\trigger;
class Checksum
{
    /**
     * @throws CorruptComponentPayloadException
     * @throws FileSystemException
     * @throws RuntimeException
     */
    function verify($snapshot): void
    {
        $checksum = $snapshot['checksum'];
        unset($snapshot['checksum']);
        trigger('checksum.verify', $checksum, $snapshot);
        if ($checksum !== $comparitor = $this->generate($snapshot)) {
            trigger('checksum.fail', $checksum, $comparitor, $snapshot);
            throw new CorruptComponentPayloadException();
        }
    }
    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    function generate(array $snapshot): string
    {
        $hashKey = $this->deploymentConfig->get('crypt/key');
        $checksum = hash_hmac('sha256', $this->serializer->serialize($snapshot), $hashKey);
        trigger('checksum.generate', $checksum, $snapshot);
        return $checksum;
    }
    function __construct(private readonly DeploymentConfig $deploymentConfig, private readonly SerializerInterface $serializer)
    {
        //
    }
}