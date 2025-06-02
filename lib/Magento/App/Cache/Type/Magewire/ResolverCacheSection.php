<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\App\Cache\Type\Magewire;

use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magento\App\Cache\Type\Magewire as MagewireCacheType;

class ResolverCacheSection
{
    public function __construct(
        private readonly MagewireCacheType $magewire,
        private readonly SerializerInterface $serializer
    ) {
        //
    }

    public function save(array $data): bool
    {
        return $this->magewire->save($this->serializer->serialize($data), 'resolvers', ['resolvers']);
    }

    public function fetch(callable|null $filter = null): array
    {
        $resolvers = $this->magewire->load('resolvers');
        $resolvers = ! $resolvers ? [] : $resolvers;

        return $filter ? $filter($resolvers) : $resolvers;
    }
}
