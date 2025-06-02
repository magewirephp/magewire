<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext;

use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerTypeContext;

class HandlerTypeMetaData extends HandlerTypeContext
{
    private string|null $alias = null;

    public function withAlias(?string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function getAlias(): string|null
    {
        return $this->alias;
    }

    public function hasAlias(): bool
    {
        return is_string($this->alias);
    }
}
