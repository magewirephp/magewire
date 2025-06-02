<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleComponents;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Checksum as ChecksumHandler;

class Snapshot
{
    /**
     * @param JsonSerializer $serializer
     * @param ChecksumHandler $checksumHandler
     * @param mixed $data
     * @param mixed $memo
     * @param string $checksum
     */
    function __construct(
        private readonly JsonSerializer $serializer,
        private readonly ChecksumHandler $checksumHandler,
        private mixed $data = [],
        private mixed $memo = [],
        private string $checksum = ''
    ) {
        //
    }

    /**
     * @param array $data
     * @return $this
     */
    function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $memo
     * @return $this
     */
    function setMemo(array $memo): self
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * @return array
     */
    function getMemo(): array
    {
        return $this->memo;
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    function generateChecksum(): self
    {
        $this->checksum = $this->checksumHandler->generate(
            $this->toArray(['checksum'])
        );

        return $this;
    }

    /**
     * @return string
     */
    function getChecksum(): string
    {
        return $this->checksum;
    }

    /**
     * @param string $property
     * @param $value
     * @return $this
     */
    function setDataValue(string $property, $value): self
    {
        $this->data[$property] = $value;
        return $this;
    }

    /**
     * @param string $property
     * @return mixed|null
     */
    function getDataValue(string $property): mixed
    {
        return $this->data[$property] ?? null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    function setMemoValue(string $key, mixed $value): Snapshot
    {
        $this->memo[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    function getMemoValue(string $key, mixed $default = null): mixed
    {
        return $this->memo[$key] ?? $default;
    }

    /**
     * @param array $exclude
     * @return array
     */
    function toArray(array $exclude = []): array
    {
        return array_diff_key([
            'data' => $this->data,
            'memo' => $this->memo,
            'checksum' => $this->checksum
        ], array_flip($exclude));
    }

    /**
     * @param array $exclude
     * @return string
     */
    function toString(array $exclude = []): string
    {
        return $this->serializer->serialize($this->toArray($exclude));
    }
}
