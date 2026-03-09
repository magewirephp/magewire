<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\View\UtilsInterface;
use Psr\Log\LoggerInterface;

class Layout implements UtilsInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
        
    }

    /**
     * Processes a block as a container and returns all its child blocks.
     *
     * @return array<int, AbstractBlock>
     */
    public function containerizeBlock(AbstractBlock|false $block): array
    {
        if ($block) {
            return array_filter(array_map(function (string $child) use ($block) {
                try {
                    return $block->getChildBlock($block->getLayout()->getElementAlias($child) ?? $child);
                } catch (LocalizedException $exception) {
                    $this->logger->critical(sprintf('Can not retrieve child block: %s', $child), ['exception' => $exception]);
                }

                return false;
            }, $block->getChildNames()));
        }

        return [];
    }

    /**
     * Determines whether the current block can be containerized.
     */
    public function canContainerizeBlock(AbstractBlock|false $block): bool
    {
        if ($block) {
            return count($this->containerizeBlock($block)) !== 0;
        }

        return false;
    }

    /**
     * Renders all child blocks of the given block.
     */
    public function renderBlockAsContainer(AbstractBlock|false $block): string
    {
        if ($block) {
            return implode('', array_map(static function (object $child) {
                return ($child instanceof AbstractBlock ? $child->toHtml() : '') . PHP_EOL;
            }, $this->containerizeBlock($block)));
        }

        return '';
    }

    public function getChild(AbstractBlock|false $block, string $alias, array $data = []): AbstractBlock|null
    {
        if ($block) {
            $child = $block->getChildBlock($alias);

            if ($child instanceof AbstractBlock) {
                if (! empty($data)) {
                    $child->addData($data);
                }

                return $child;
            }
        }

        return null;
    }

    public function getChildHtml(AbstractBlock|false $block, string $alias, array $data = []): string
    {
        $child = $this->getChild($block, $alias, $data);

        return $child ? $child->toHtml() : '';
    }
}
