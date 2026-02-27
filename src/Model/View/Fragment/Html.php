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
use Throwable;

class Html extends Fragment
{
    protected array $attributes = [];

    public function __construct(
        LoggerInterface $logger,
        Escaper $escaper,
        array $modifiers = []
    ) {
        parent::__construct($logger, $escaper, $modifiers);
    }

    /**
     * Wraps pre-rendered content in a fragment container.
     *
     * Provides a convenient way to create a fragment when you already have the rendered HTML content,
     * eliminating the need to use the traditional start/end fragment workflow.
     * The content is wrapped in the appropriate fragment markup and returned as a complete fragment.
     */
    public function wrap(string $input): string
    {
        // Avoid ob_start by buffering simulation.
        $this->buffering = true;
        $this->raw = $input;

        try {
            $this->start();
            $output = $this->render();
            $this->end();

            return $output;
        } catch (Throwable $exception) {
            return $this->handleRenderException($exception);
        }
    }

    public function withAttribute(string $name, string|float|int|null $value = null, string $area = 'root'): static
    {
        if ($value === null) {
            $this->attributes[$area][] = $name;
        } else {
            $this->attributes[$area][$name] = $value;
        }

        return $this;
    }

    public function withAttributes(array $attributes, string $area = 'root'): static
    {
        foreach ($attributes as $name => $value) {
            $this->withAttribute($name, $value, $area);
        }

        return $this;
    }

    protected function render(): string
    {
        $render = parent::render();
        $attributes = $this->getAreaAttributes('root');

        if (! empty($attributes)) {
            $attributeStrings = [];

            foreach ($attributes as $attribute => $value) {
                $attributeStrings[] = is_numeric($attribute) ? $value : $attribute . '="' . $this->escaper->escapeHtmlAttr($value) . '"';
            }

            if (! empty($attributeStrings)) {
                $attributeString = ' ' . implode(' ', $attributeStrings);
                $render = preg_replace('/^(<[^>\s]+)/', '$1' . $attributeString, $render, 1);
            }
        }

        return trim($render);
    }

    protected function getAttributes(): array
    {
        return $this->attributes;
    }

    protected function getAreaAttributes(string $area): array
    {
        if ($area === 'root') {
            // Filter those who are not an 'area' (an array value).
            $attributes = array_filter($this->attributes, fn ($value) => ! is_array($value));

            return array_merge($this->attributes[$area] ?? [], $attributes);
        }

        return $this->attributes[$area] ?? [];
    }
}
