<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\Framework\View;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Layout\BuilderInterface;
use Magento\Framework\View\LayoutFactory as MagentoLayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Support\Concerns\WithFactory;

class DynamicLayoutBuilder implements BuilderInterface
{
    use WithFactory;

    private array $builds = [];

    public function __construct(
        private readonly MagentoLayoutFactory $magentoLayoutFactory,
        private readonly LayoutInterface $layout
    ) {
        
    }

    /**
     * @return LayoutInterface
     * @throws LocalizedException
     */
    public function build(bool $force = false): LayoutInterface
    {
        $handles = $this->layout->getUpdate()->getHandles();

        sort($handles);
        $hash = hash('xxh3', json_encode(($handles)));

        // Early return when a version with the identical handles already exists.
        if ($force === false && array_key_exists($hash, $this->builds)) {
            return $this->builds[$hash];
        }

        // Register build so it only run once to avoid running infinitely.
        $this->builds[$hash] = $this->layout;

        $this->layout->getUpdate()->load($handles);
        $this->layout->generateXml();
        $this->layout->generateElements();

        return $this->builds[$hash];
    }

    public function reset(): static
    {
        $this->builds = [];

        return $this;
    }

    /**
     * @throws LocalizedException
     */
    public function rebuild(): LayoutInterface
    {
        return $this->build(true);
    }
}
