<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Plugin\Magento\Framework\View\TemplateEngine;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php as Subject;
use function Magewirephp\Magewire\trigger;

class Php
{
    public function beforeRender(
        Subject $subject,
        BlockInterface $block,
        string $filename,
        array $dictionary = []
    ): array {
        $renderTemplate = trigger('magento:template:render', $block, $filename, $dictionary);

        $result = ['block' => $block, 'filename' => $filename, 'dictionary' => $dictionary];
        $result = $renderTemplate($result);

        return [$result['block'] ?? $block, $result['filename'] ?? $filename, $result['dictionary'] ?? $dictionary];
    }

    public function afterRender(Subject $subject, string $html): string
    {
        $rendered = trigger('magento:template:rendered', $html);
        return $rendered($html);
    }
}
