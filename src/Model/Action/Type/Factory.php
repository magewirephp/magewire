<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action\Type;

use Magento\Framework\ObjectManagerInterface;

class Factory
{
    protected ?ObjectManagerInterface $objectManager;

    /**
     * Factory constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $type
     * @return mixed|string
     */
    public function create(string $type)
    {
        return $this->objectManager->create($type);
    }
}
