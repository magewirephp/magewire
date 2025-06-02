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

class HandlerTypeTargets extends HandlerTypeContext
{
    private ?string $query = null;
//    private ?BuilderInterface $builder = null;

    public function id(string $id): static
    {
        return $this;
    }

//    public function build(): static
//    {
//        $this->query = $this->builder?->__toString();
//
//        return $this;
//    }

//    public function query(): QueryBuilder
//    {
//        return $this->builder ??= ObjectManager::getInstance()->create(QueryBuilder::class, [
//            'context' => $this
//        ]);
//    }

    public function __toString(): string
    {
        return $this->query ?? '';
    }
}
