<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

use Magento\Framework\Escaper;
use Magewirephp\Magewire\Model\View\Fragment;
use Psr\Log\LoggerInterface;

class Html extends Fragment
{
    private array $attributes = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Escaper $escaper,
        private readonly array $modifiers = []
    ) {
        parent::__construct($this->logger, $this->modifiers);
    }

    public function start(): static
    {
        return parent::start()->withValidator(static fn ($html) => str_starts_with($html, '<'));
    }

    public function setAttribute(string $name, string|float|int|null $value = null, string $area = 'root'): static
    {
        if ($value === null) {
            $this->attributes[$area] = $name;
        } else {
            $this->attributes[$area][$name] = $value;
        }

        return $this;
    }

    protected function render(): string
    {
        $render = parent::render();

        // WIP: Does need a performance gain trying to avoid a preg_replace, does the job for now.
        foreach ($this->getAreaAttributes('root') as $attribute => $value) {
            if (is_numeric($attribute)) {
                $render = preg_replace('/^(<[^>\s]+)/', '$1 ' . $value, $render);
            } else {
                $render = preg_replace('/^(<[^>\s]+)/', '$1 ' . $attribute . '="' . $this->escaper->escapeHtmlAttr($value) . '"', $render);
            }
        }

        return $render;
    }

    protected function getAttributes(): array
    {
        return $this->attributes;
    }

    protected function getAreaAttributes(string $area): array
    {
        return $this->attributes[$area] ?? [];
    }
}
