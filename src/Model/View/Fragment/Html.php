<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

use Magewirephp\Magewire\Model\View\ComponentAttributeBag;
use Magewirephp\Magewire\Model\View\Fragment;
use Magewirephp\Magewire\Support\DataCollection;
use Throwable;

class Html extends Fragment
{
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
        $attributes = $this->attributes()->target($area);
        $value ? $attributes->set($name, $value, true) : $attributes->push($value);

        return $this;
    }

    public function withAttributes(array $attributes, string $area = 'root'): static
    {
        $this->attributes()->target($area)->fill($attributes);

        return $this;
    }

    protected function attributes(): DataCollection
    {
        return $this->properties()->target('attributes');
    }

    protected function properties(): DataCollection
    {
        $properties = parent::properties();
        $properties->subset('attributes', ComponentAttributeBag::class);

        return $properties;
    }
}
