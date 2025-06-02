<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Concerns;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

trait AsDataObject
{
    private array $data = [];

    function getData(string $key, $default = null): mixed
    {
        if (property_exists(self::class, 'data')) {
            return $this->data[$key] ?? $default;
        }

        return $default;
    }

    function getDataByPath(string $path, $default = null, string $separator = '.')
    {
        $keys = explode($separator, $path);
        $data = $this->data;

        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return $default;
            }
        }

        if ($this->data === $data) {
            return $default;
        }

        return $data ?? $default;
    }

    function setData(string $key, mixed $value): self
    {
        if (property_exists(self::class, 'data')) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    function hasData($key): bool
    {
        if (property_exists(self::class, 'data')) {
            if (empty($key) || ! is_string($key)) {
                return ! empty($this->data);
            }

            return array_key_exists($key, $this->data);
        }

        return false;
    }

    /**
     * @throws LocalizedException
     */
    function __call($method, $args)
    {
        switch (substr((string) $method, 0, 3)) {
            case 'get':
                return $this->getData(substr($method, 3));
            case 'set':
                return $this->setData(substr($method, 3), $args[0] ?? null);
            case 'has':
                return isset($this->data[substr($method, 3)]);
        }

        throw new LocalizedException(
            new Phrase('Invalid method %1::%2', [get_class($this), $method])
        );
    }

    function populate(array $data): void
    {
        $this->data = $data;
    }
}
