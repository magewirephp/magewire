<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\App\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magento\App\Cache\Type\Magewire\ResolverCacheSection;

class Magewire extends TagScope
{
    public const TYPE_IDENTIFIER = 'magewire';
    public const CACHE_TAG = 'MAGEWIRE';

    public function __construct(
        private readonly FrontendPool $cacheFrontendPool,
        private readonly SerializerInterface $serializer
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }

    public function load($identifier)
    {
        $data = parent::load($identifier);

        return is_string($data) ? $this->serializer->unserialize($data) : $data;
    }

    public function resolvers()
    {
        return ObjectManager::getInstance()->get(ResolverCacheSection::class);
    }
}
