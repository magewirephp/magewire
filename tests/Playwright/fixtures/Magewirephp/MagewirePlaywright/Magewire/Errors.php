<?php

declare(strict_types=1);

namespace Magewirephp\MagewirePlaywright\Magewire;

use Magewirephp\Magewire\Component;
use Magewirephp\MagewirePlaywright\Model\Failure;

class Errors extends Component
{
    public int $count = 0;

    public function __construct(
        private readonly Failure $failure
    ) {
    }

    public function increment(): void
    {
        $this->count++;
    }

    public function failException(): void
    {
        $this->failure->raise('exception');
    }

    public function failTypeError(): void
    {
        $this->failure->raise('type-error');
    }
}
