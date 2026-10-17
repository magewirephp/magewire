<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManager\ConfigInterface;

class Factory
{
    /**
     * Concept: resolve named dependencies in the context of an existing consumer.
     *
     * Pass the original DI name explicitly when the consumer was created as a virtual type.
     */
    public static function for(object $consumer, string|null $consumerType = null): DependencyResolver
    {
        $objectManager = ObjectManager::getInstance();

        return new DependencyResolver($consumer, $objectManager, $objectManager->get(ConfigInterface::class), $consumerType);
    }

    /**
     * @template T
     * @param class-string<T> $type
     * @return T
     */
    public static function create(string $type, array $arguments = [])
    {
        // POC: Factories can be singletons because of their single responsibility.
        if (str_ends_with($type, 'Factory')) {
            return static::get($type);
        }

        return ObjectManager::getInstance()->create($type, $arguments); // phpcs:ignore
    }

    /**
     * @template T
     * @param class-string<T> $type
     * @return T
     */
    public static function get(string $type)
    {
        return ObjectManager::getInstance()->get($type); // phpcs:ignore
    }
}
