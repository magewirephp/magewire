<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

/**
 * Fragment component representing a named slot within a template.
 *
 * Slots capture content during template rendering without immediately echoing it.
 * Instead, the content is registered in the SlotsRegistry under the slot's variant
 * name, making it available for later retrieval and placement within parent templates.
 *
 * Unlike standard fragments, slots do not create nested tracking contexts - they
 * simply register their content as a named slot in the current area's registry.
 * The slot's name is determined by its variant property.
 */
class Slot extends Component
{
    // Prevent the slot's captured output from being directly echoed.
    // Slots are buffered internally and registered for later use in the component template.
    protected bool $echo = false;
    protected bool $trackable = false;

    /**
     * Begin slot content capture.
     *
     * Registers the slot with the registry using the variant as the slot name,
     * then starts output buffering to capture the slot's content.
     */
    public function start(): static
    {
        $this->slots()->register($this->variant(), $this);

        return parent::start();
    }

    /**
     * Complete slot content capture and register the output.
     *
     * Finalizes the fragment rendering to ensure all buffered output is captured,
     * then registers the captured content with the SlotsRegistry under this slot's
     * variant name, making it available for retrieval in parent templates.
     */
    public function end(): static
    {
        // First, complete the fragment rendering to ensure output is fully buffered.
        parent::end();

        // Push as a new entry — every `<slot:name>` block is one logical
        // value. Multiple re-assignments stack as separate entries so the
        // template can iterate previous values via foreach, while echoing
        // the snapshot still returns only the latest (Slot::__toString
        // returns the last component).
        $this->slots()->get($this->variant())->push($this->output);

        return $this;
    }
}
