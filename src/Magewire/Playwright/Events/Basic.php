<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\Events;

use Magewirephp\Magewire\Attributes\On;
use Magewirephp\Magewire\Component;

/** @mago-expect lint:too-many-methods */
class Basic extends Component
{
    public string $result = 'none';

    public string $scope = 'resolved';

    protected $listeners = [
        'class:kept' => 'onClassKept',
        'class:removed' => 'onClassRemoved',
        'class:replaced' => 'onClassOriginal',
        'shorthandListener',
        'dynamic:{scope}' => 'onDynamicListener'
    ];

    protected $loader = [
        'onClassKept' => 'Class loading',
        'onClassRemoved' => 'Removed loading'
    ];

    public function onClassKept(): void
    {
        $this->result = 'class-kept';
    }

    public function onClassRemoved(): void
    {
        $this->result = 'class-removed';
    }

    public function onClassOriginal(): void
    {
        $this->result = 'class-original';
    }

    public function onClassReplacement(): void
    {
        $this->result = 'class-replacement';
    }

    public function shorthandListener(): void
    {
        $this->result = 'shorthand-listener';
    }

    public function onDynamicListener(): void
    {
        $this->result = 'dynamic-listener';
    }

    #[On('attribute:kept')]
    public function onAttributeKept(): void
    {
        $this->result = 'attribute-kept';
    }

    #[On('attribute:removed')]
    public function onAttributeRemoved(): void
    {
        $this->result = 'attribute-removed';
    }

    #[On('attribute:replaced')]
    public function onAttributeOriginal(): void
    {
        $this->result = 'attribute-original';
    }

    public function onAttributeReplacement(): void
    {
        $this->result = 'attribute-replacement';
    }

    public function onLayoutAdded(): void
    {
        $this->result = 'layout-added';
    }

    public function onLayoutOriginal(): void
    {
        $this->result = 'layout-original';
    }

    public function onLayoutReplacement(): void
    {
        $this->result = 'layout-replacement';
    }

    public function onLayoutRemoved(): void
    {
        $this->result = 'layout-removed';
    }

    public function onModifierAdded(): void
    {
        $this->result = 'modifier-added';
    }

    public function onModifierReplacement(): void
    {
        $this->result = 'modifier-replacement';
    }
}
