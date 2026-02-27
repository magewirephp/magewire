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

/**
 * Fragment element representing a named slot within a template.
 *
 * Slots capture content during template rendering without immediately echoing it.
 * Instead, the content is registered in the SlotsRegistry under the slot's variant
 * name, making it available for later retrieval and placement within parent templates.
 *
 * Unlike standard fragments, slots do not create nested tracking contexts - they
 * simply register their content as a named slot in the current area's registry.
 * The slot's name is determined by its variant property.
 *
 * @deprecated Work in progress, do not use in production.
 */
class Slot extends Fragment\Element
{
    // Prevent the slot's captured output from being directly echoed.
    // Slots are buffered internally and registered for later use in the component template.
    protected bool $echo = false;

    /**
     * Begin slot content capture.
     *
     * Registers the slot with the registry using the variant as the slot name,
     * then starts output buffering to capture the slot's content.
     */
    public function start(): static
    {
        // Start a new slot named by the variant.
        $this->slotsRegistry->register($this->variant, $this);

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

        // Register the captured output as the content for this named slot (identified by variant).
        // This makes it available in the component view as a slot variable (e.g., $header).
        $this->slotsRegistry->update($this->variant, $this->output);

        return $this;
    }

    /**
     * Override to prevent slot area tracking.
     *
     * Slots do not create nested tracking contexts in the registry since they
     * simply register content as named slots rather than managing their own
     * slot collections. This no-op implementation ensures track() calls are
     * safely ignored.
     */
    public function track(): static
    {
        return $this;
    }

    /**
     * Override to prevent slot area untracking.
     *
     * Slots do not manage tracking contexts. This no-op implementation ensures
     * untrack() calls are safely ignored, maintaining the current area's tracking
     * state without interfering with the parent fragment's lifecycle.
     */
    public function untrack(): static
    {
        return $this;
    }
}
