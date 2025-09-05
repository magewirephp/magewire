<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\App\Cache;

use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magento\App\Cache\Type\Magewire as MagewireCacheType;

abstract class MagewireCacheSection
{
    protected string $identifier;

    protected array $tags = [];
    protected int|null $ttl = null;

    public function __construct(
        private readonly MagewireCacheType $magewireCacheType,
        private readonly SerializerInterface $serializer
    ) {
        //
    }

    public function save(array $data, int|null $ttl = null): bool
    {
        if (empty($this->tags)) {
            $this->tags[] = $this->identifier;
        }

        return $this->magewireCacheType->save($this->serializer->serialize($data), $this->identifier, $this->tags, $ttl ?? $this->ttl);
    }

    public function fetch(): array
    {
        $data = $this->magewireCacheType->load($this->identifier);

        return ! $data ? [] : $data;
    }
}
