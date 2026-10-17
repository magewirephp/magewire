<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use Psr\Log\LoggerInterface;

require_once __DIR__ . '/ExamplePanel.php';

class ExamplePanelWithDependency extends ExamplePanel
{
    public function __construct(
        public readonly LoggerInterface $logger
    ) {
    }
}
