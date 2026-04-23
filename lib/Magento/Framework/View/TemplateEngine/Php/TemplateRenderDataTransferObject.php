<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\Framework\View\TemplateEngine\Php;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Support\Contracts\DataTransferObjectInterface;

class TemplateRenderDataTransferObject implements DataTransferObjectInterface
{
    public function __construct(
        private readonly AbstractBlock $block,
        private string $filename,
        private array $dictionary
    ) {
    }

    public function block(): AbstractBlock
    {
        return $this->block;
    }

    public function filename(string|null $filename = null): string
    {
        if ($filename !== null) {
            $this->filename = $filename;
        }

        return $this->filename;
    }

    public function dictionary(array|null $dictionary = null): array
    {
        if ($dictionary !== null) {
            // Deliberately not using an array merge for performance reasons.
            foreach ($dictionary as $key => $value) {
                $this->dictionary[$key] = $value;
            }
        }

        return $this->dictionary;
    }

    public function existsInDictionary(string $key): bool
    {
        return isset($this->dictionary[$key]);
    }
}
