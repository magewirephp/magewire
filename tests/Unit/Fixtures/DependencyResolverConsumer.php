<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use ArrayObject;

class DependencyResolverConsumer
{
    public function __construct(
        private readonly ArrayObject $service,
        ArrayObject $unstored
    ) {
        // The second argument deliberately is not retained as a property.
    }
}
