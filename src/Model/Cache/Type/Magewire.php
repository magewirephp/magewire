<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\Serialize\SerializerInterface;

class Magewire extends TagScope
{
    public const TYPE_IDENTIFIER = 'magewire';
    public const CACHE_TAG = 'MAGEWIRE';

    public const SECTION_RESOLVERS = 'resolvers';

    protected SerializerInterface $serializer;

    public function __construct(
        FrontendPool $cacheFrontendPool,
        SerializerInterface $serializer
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);

        $this->serializer = $serializer;
    }

    public function load($identifier)
    {
        $data = parent::load($identifier);
        return is_string($data) ? $this->serializer->unserialize($data) : $data;
    }

    public function saveResolvers(array $data): bool
    {
        return $this->save($this->serializer->serialize($data), self::SECTION_RESOLVERS, [self::SECTION_RESOLVERS]);
    }
}
