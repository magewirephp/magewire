<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Escaper;
use Magewirephp\Magewire\Model\View\FragmentFactory;
use Magewirephp\Magewire\Model\View\UtilsInterface;

class Template implements UtilsInterface
{
    public function __construct(
        private readonly Escaper $escaper,
        private readonly FragmentFactory $fragmentFactory
    ) {
        //
    }

    public function echoCodeComment(
        string $text,
        bool $uppercased = false,
        string|null $subsection = null,
        string|array|null $state = ApplicationState::MODE_DEVELOPER
    ): string {
        $text = $uppercased ? strtoupper($text) : strtolower($text);
        $text = $subsection ? ucfirst($subsection) . ': ' .$text : $text;
        $text = trim($text, '.');

        $states = [
            null,
            ApplicationState::MODE_DEVELOPER,
            ApplicationState::MODE_DEFAULT,
            ApplicationState::MODE_PRODUCTION
        ];

        if (in_array($state, $states) || is_array($state) && empty(array_diff($state, array_filter($states)))) {
            return '<!-- Magewire: '. $this->escaper->escapeHtml($text) .'. -->' . PHP_EOL;
        }

        return '';
    }

    public function fragment(): FragmentFactory
    {
        return $this->fragmentFactory;
    }
}
