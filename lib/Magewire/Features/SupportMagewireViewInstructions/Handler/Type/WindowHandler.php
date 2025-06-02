<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type;

use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerType;

class WindowHandler extends HandlerType
{
    public function onResize(int|null $pixels = null): static
    {
        $this->data()->set('listeners', ['options' => [
            'pixels' => $pixels
        ]], 'resize');

        return $this;
    }

    public function onResizeUp(int|null $pixels = null): static
    {
        $this->data()->set('args.options.pixels', $pixels, 'resize-up');

        return $this;
    }

    public function onResizeDown(int|null $pixels = null): static
    {
        $this->data()->set('args.options.pixels', $pixels, 'resize-down');

        return $this;
    }

    public function onStorageChange(): static
    {
        $this->listen()->for('storage');

        return $this;
    }

    public function onPopState(): static
    {
        $this->listen()->for('popstate');

        return $this;
    }
}
