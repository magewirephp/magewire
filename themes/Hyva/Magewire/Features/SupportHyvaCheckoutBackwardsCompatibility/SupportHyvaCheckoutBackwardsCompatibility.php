<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithHyva\Magewire\Features\SupportHyvaCheckoutBackwardsCompatibility;

use Hyva\Checkout\Magewire\Checkout\AddressView\AbstractMagewireAddressForm;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutLifecycleManager;
use Psr\Log\LoggerInterface;
use ReflectionException;

use function Magewirephp\Magewire\after;
use function Magewirephp\Magewire\store;

/**
 * Bridges Magewire v1 (Livewire v2) component behavior for Hyvä Checkout.
 *
 * Hyvä Checkout was built on Magewire v1, which used Livewire v2 conventions. Magewire v3
 * (Livewire v3) introduced breaking changes to wire directives and entangle behavior:
 *
 *   - wire:model (instant sync)  → wire:model.live
 *   - wire:model.defer           → wire:model (now the default)
 *   - wire:model.lazy            → wire:model.blur
 *   - $wire.entangle() (live)    → $wire.entangle() (deferred by default)
 *
 * This feature flags components that need backwards compatibility by pushing a`bc.enabled`
 * memo flag into the snapshot. The flag is resolved through three sources (in priority order):
 *
 *   1. The #[HandleBackwardsCompatibility] attribute on the component class
 *   2. A previously hydrated value from the component's data store
 *   3. Whether the component lives inside the 'hyva-checkout-main' layout container
 *
 * The frontend JS (in magewire-attributes.phtml and magewire-components.phtml) reads this
 * flag to automatically migrate wire:model directives and make entangle default to live,
 * so existing Hyvä Checkout components work without code changes.
 */
class SupportHyvaCheckoutBackwardsCompatibility extends ComponentHook
{
    public function __construct(
        private readonly LayoutLifecycleManager $renderLifecycleManager,
        private readonly LoggerInterface $logger,
        private readonly TemporaryHydrationRegistry $temporaryHydrationRegistry,
        private readonly \Magewirephp\Magewire\Features\SupportEvents\SupportEvents $supportEvents
    ) {
    }

    public function provide()
    {
        after('dehydrate', function (Component $component, ComponentContext $context) {
            if ($context->isMounting() || ! $component instanceof AbstractMagewireAddressForm) {
                return;
            }

            $effects = $context->getEffects();
            $currentDispatches = $effects->getData('dispatches') ?? [];
            $newDispatches = $this->supportEvents->getServerDispatchedEvents($component);

            if (empty($currentDispatches)) {
                $currentDispatches = $newDispatches;
            } else {
                foreach ($currentDispatches as $current) {
                    foreach ($newDispatches as $new) {
                        if ($current['name'] === $new['name']) {
                            continue;
                        }

                        $currentDispatches[] = $new;
                    }
                }
            }

            $context->addEffect('dispatches', $currentDispatches);
        });
    }

    public function hydrate($memo): void
    {
        if (! isset($memo['bc']['enabled'])) {
            return;
        }

        $this->temporaryHydrationRegistry->push($this->component()->id());
    }

    public function dehydrate(ComponentContext $context): void
    {
        // For backwards compatibility, if an evaluation effect is present, push it into the 'serverMemo'
        // under the 'bc' key so older clients that rely on this memo structure continue to work correctly.
        $bcEffect = $context->getEffects()->getData('bc') ?? [];
        $evaluationEffect = $context->getEffects()->getData('evaluation');

        if (is_array($evaluationEffect)) {
            $bcEffect['serverMemo']['evaluation'] = $evaluationEffect;
            $context->pushEffect('bc', $bcEffect['serverMemo'], 'serverMemo');
        }

        try {
            $backwardsCompatibilityActive = $this->component()
                ? store($this->component())->get('magewire:bc')
                : false;

            // When still null, a Magewire component is dynamically injected onto the page via a subsequent
            // Magewire request, it can not match any of the above use cases. Herefor, a unique
            // situation occurs needing to search within the lifecycle to try and figure out if
            // any of the requested components, rendered this child component.
            if ($backwardsCompatibilityActive === false) {
                // When still null, lets check if this component sits within the Hyvä Checkout Main component.
                $backwardsCompatibilityActive = $this->renderLifecycleManager->target('magewire')
                    ->within('hyva-checkout-main');

                if ($backwardsCompatibilityActive === false) {
                    foreach ($this->temporaryHydrationRegistry->list() as $value) {
                        $this->temporaryHydrationRegistry->pop($value);

                        // Found look upwards in the layout lifecycle, so flag it and break the current loop.
                        if ($this->renderLifecycleManager->target('magewire')->within($value)) {
                            $backwardsCompatibilityActive = true;
                            break;
                        }
                    }
                }
            }

            store($this->component())->set(
                'magewire:bc',
                is_bool($backwardsCompatibilityActive)
                    ? $backwardsCompatibilityActive
                    : false
            );
        } catch (ReflectionException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        $backwardsCompatibilityActive = $this->component()
            ? store($this->component())->get('magewire:bc')
            : false;

        $context->pushMemo('bc', $backwardsCompatibilityActive ?? false, 'enabled');
    }
}
