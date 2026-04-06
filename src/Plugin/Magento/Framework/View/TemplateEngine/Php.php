<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Plugin\Magento\Framework\View\TemplateEngine;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\TemplateEngine\Php as Subject;
use Magewirephp\Magento\Framework\View\TemplateEngine\Php\TemplateRenderDataTransferObject;
use Magewirephp\Magewire\Support\Factory;
use function Magewirephp\Magewire\trigger;

class Php
{
    private array $renderers = [];

    public function beforeRender(
        Subject $subject,
        AbstractBlock $block,
        string $filename,
        array $dictionary = []
    ): array {
        $dto = Factory::create(TemplateRenderDataTransferObject::class, [
            'block' => $block,
            'filename' => $filename,
            'dictionary' => $dictionary,
        ]);

        $this->renderers[] = trigger('magento:template:render', $dto, $subject);

        return [$dto->block(), $dto->filename(), $dto->dictionary()];
    }

    public function afterRender(Subject $subject, string $html): string
    {
        $finish = array_pop($this->renderers);

        return $finish ? $finish($html, $subject) : $html;
    }
}
