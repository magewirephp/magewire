<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithHyva\Magewire\Features\SupportHyvaCheckoutBackwardsCompatibility;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutLifecycleManager;
use Magewirephp\Magewire\Support\AttributesReader;
use Magewirephp\Magewire\Support\Random;
use Psr\Log\LoggerInterface;
use ReflectionException;

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
        private readonly TemporaryHydrationRegistry $temporaryHydrationRegistry
    ) {
    }

    public function hydrate($memo): void
    {
        if (! isset($memo['bc']['enabled'])) {
            return;
        }

        store($this->component)->set('bc.enabled', $memo['bc']['enabled']);
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
            $within = AttributesReader::for($this->component())->first(HandleBackwardsCompatibility::class);

            if ($within instanceof HandleBackwardsCompatibility) {
                $within = $within->isBackwardsCompatible();
            }

            // A: First check if none was set during hydration.
            $within ??= $this->component ? store($this->component)->get('bc.enabled') : null;
            // B: Try to reach out to the actual use case.
            $within ??= $this->renderLifecycleManager->target('magewire')->within('hyva-checkout-main');

            // C: When a Magewire component is dynamically injected onto the page via a subsequent
            // Magewire request, it can not match any of the above use cases. Herefor, a unique
            // situation occurs needing to search within the lifecycle to try and figure out if
            // any of the requested components, rendered this child component.
            if (! $within) {
                foreach ($this->temporaryHydrationRegistry->list() as $value) {
                    $this->temporaryHydrationRegistry->pop($value);

                    // Found look upwards in the layout lifecycle, so flag it and break the current loop.
                    if ($this->renderLifecycleManager->target('magewire')->within($value)) {
                        $within = true;
                        break;
                    }
                }
            }
        } catch (ReflectionException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        $context->pushMemo('bc', $within ?? false, 'enabled');
        $context->pushMemo('bc', Random::integer(), 'key');
    }
}
