<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\Escaper;
use Magento\Framework\Serialize\SerializerInterface;

class Functions
{
    protected SerializerInterface $serializer;
    protected Escaper $escape;

    public function __construct(
        SerializerInterface $serializer,
        Escaper $escape
    ) {
        $this->serializer = $serializer;
        $this->escape = $escape;
    }

    public function map(callable $callback, array $data): array
    {
        $keys  = array_keys($data);
        $items = array_map($callback, $data, $keys);

        return array_combine($keys, $items);
    }

    public function mapWithKeys(callable $callback, array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return $result;
    }

    public function escapeStringForHtml($subject): string
    {
        if (is_string($subject) || is_numeric($subject)) {
            return $this->escape->escapeHtml($subject);
        }

        return $this->escape->escapeHtml($this->serializer->serialize($subject));
    }
}
