<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Magento\Framework\App\State as ApplicationState;
use Magewirephp\Magewire\Support\Random;
use Stringable;

readonly class PlacementEntry implements Stringable
{
    private string $code;

    public function __construct(
        private ApplicationState $applicationState,
        private string $content,
        private string|null $scope = null,
        private string|null $name = null,
        string|null $code = null
    ) {
        $this->code = $code ?? Random::alphabetical(8, true);
    }

    public function __toString(): string
    {
        if ($this->isProductionMode()) {
            return $this->content;
        }

        return $this->content . PHP_EOL . $this->comment(sprintf('Magewire: Script placement "%s".', $this->code));
    }

    public function sourceComment(): string
    {
        if ($this->isProductionMode()) {
            return '';
        }

        return $this->comment(sprintf('Magewire: Script "%s" transferred to "%s".', $this->code, $this->name ?? 'unknown'));
    }

    private function isProductionMode(): bool
    {
        return $this->applicationState->getMode() === ApplicationState::MODE_PRODUCTION;
    }

    private function comment(string $text): string
    {
        return '<!-- ' . str_replace('--', '- -', $text) . ' -->';
    }
}
