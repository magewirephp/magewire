<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Component.
 */
class Functions
{
    /** @var SerializerInterface */
    private $serializer;

    /**
     * Functions constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @param callable $callback
     * @param array    $data
     *
     * @return array
     */
    public function map(callable $callback, array $data): array
    {
        $keys = array_keys($data);
        $items = array_map($callback, $data, $keys);

        return array_combine($keys, $items);
    }

    /**
     * @param callable $callback
     * @param array    $data
     *
     * @return array
     */
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

    /**
     * @param $subject
     *
     * @return string
     */
    public function escapeStringForHtml($subject): string
    {
        if (is_string($subject) || is_numeric($subject)) {
            return htmlspecialchars($subject);
        }

        return htmlspecialchars($this->serializer->serialize($subject));
    }
}
