<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment\Element;

use Magewirephp\Magewire\Model\View\Fragment;

class Slot extends Fragment\Element
{
    // Prevent the slot's captured output from being directly echoed.
    // Slots are buffered internally and registered for later use in the component template.
    protected bool $echo = false;

    public function end(): void
    {
        // First, complete the fragment rendering to ensure output is fully buffered.
        parent::end();

        // Register the captured output as the content for this named slot (identified by $variant).
        // This makes it available in the component view as a slot variable (e.g., $header).
        $this->slotsRegistry->update($this->variant, $this->output);
    }
}
