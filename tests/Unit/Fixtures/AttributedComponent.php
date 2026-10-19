<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use Magewirephp\Magewire\Attributes\Lazy;
use Magewirephp\Magewire\Attributes\On;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;

#[HandleBackwardsCompatibility(enabled: false)]
#[Lazy]
class AttributedComponent extends Component
{
    #[On('fixture:plugged')]
    public function onPlugged(): void
    {
    }

    #[On('fixture:kept')]
    public function onKept(): void
    {
    }
}
