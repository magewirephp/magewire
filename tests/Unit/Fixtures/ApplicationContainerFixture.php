<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit;

require_once __DIR__ . '/ApplicationContainerFixtureContract.php';

class ApplicationContainerFixture implements ApplicationContainerFixtureContract
{
    public function __construct(
        public readonly string $value
    ) {
    }
}
