---
name: magewire-hyva-checkout-backwards-compatibility
description: "Hyvä Checkout-specific backwards compatibility layer for Magewire. Use when migrating Hyvä Checkout v1 (Livewire v2) components to Magewire v3, debugging the hyva-checkout-main auto-enable rule, or editing the JS BC shims under themes/Hyva (wire:model rewriting, entangle live-by-default, deprecated hook/event re-triggering, component $wire proxy). Triggers: Hyvä Checkout BC, SupportHyvaCheckoutBackwardsCompatibility, hyva-checkout-main, makeComponentBackwardsCompatible, themes/Hyva BC templates."
requires: magewire-backwards-compatibility, magewire-theming
license: MIT
metadata:
  author: Willem Poortman
---

# Magewire Hyvä Checkout Backwards Compatibility

Hyvä Checkout v1 was built against Magewire v1 / Livewire v2. This theme module (`themes/Hyva/`) ships the BC JS layer that lets those v1 components keep running on Magewire v3 without code changes.

**This skill is specific to the Hyvä theme module.** The underlying BC system is framework-level and theme-agnostic — see `magewire-backwards-compatibility` for the core trait, attribute, `memo.bc.enabled` flag, and v2→v3 behavior reference.

Other themes are free to ship their own BC implementations. Nothing about the BC framework is Hyvä-specific.

---

## What this layer provides

- A theme-scoped Feature that auto-enables BC for any component rendered inside the `hyva-checkout-main` layout container
- Four JS templates that read `memo.bc.enabled` and apply v2→v3 shims at runtime

---

## PHP Feature

`Magewirephp\MagewireCompatibilityWithHyva\Magewire\Features\SupportHyvaCheckoutBackwardsCompatibility\SupportHyvaCheckoutBackwardsCompatibility`

Responsibilities:
- Pushes `memo.bc.enabled` flag into the snapshot during `dehydrate()`
- On `hydrate()`, restores the flag from memo into the component's data store
- Resolves the flag with this priority:
  1. `#[HandleBackwardsCompatibility]` attribute on the component class
  2. Previously hydrated value from the component's data store
  3. Whether the component lives inside the `hyva-checkout-main` layout container

Registered via `themes/Hyva/etc/frontend/di.xml` alongside the other theme-scoped Features.

---

## JS Templates

Four PHTML files under `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/`:

| File | Purpose |
|------|---------|
| `magewire-hooks.phtml` | Promise-based hook runner, deprecation warnings, replacement mapping |
| `magewire-events.phtml` | Maps deprecated event names to v3 hooks, proxies component properties |
| `magewire-attributes.phtml` | Migrates `wire:model` directives on BC-enabled components |
| `magewire-components.phtml` | Proxies `Magewire.find()` and `$wire.entangle()` for BC components |

### `magewire-attributes.phtml` — wire directive rewriting

For components with `memo.bc.enabled`, intercepts `element.init` and `morph.updating` hooks and rewrites attributes. Each transformation runs independently:

```javascript
// wire:model        → wire:model.live   (v2 instant → v3 live)
// wire:model.defer  → wire:model        (v2 deferred → v3 default)
// wire:model.lazy   → wire:model.blur   (v2 lazy → v3 blur)
```

### `magewire-components.phtml` — $wire proxy + entangle

`makeComponentBackwardsCompatible()` wraps `component.$wire` in a Proxy. When `entangle` or `$entangle` is accessed, the returned function's `live` parameter defaults to `true` instead of `false`:

```javascript
// For BC components, this:
this.$wire.entangle('couponCode')
// behaves like:
this.$wire.entangle('couponCode').live
```

The wire proxy is created once per component and cached on `component.__bcWire`.

`Magewire.find(id)` is wrapped so that accessing `.__instance` returns a BC-proxied component via `makeComponentBackwardsCompatible()`. The BC component proxy intercepts property access to:
1. Return a cached `$wire` proxy with live-by-default entangle
2. Resolve BC effect mappings (e.g. `component.data` → `component.$wire` via `effects.bc.map`)
3. Support path-based resolution (`path:property.nested.value`)

### `magewire-hooks.phtml` + `magewire-events.phtml` — deprecated hooks/events

- `magewire-hooks.phtml` defines `runMagewireBackwardsCompatibleHook()` — a promise-based runner that waits for `magewire:available` before executing. In debug mode, logs deprecation warnings with the replacement hook name.
- `magewire-events.phtml` hooks into each v3 event and re-triggers the deprecated v2 event name via `Magewire.trigger()`. Also aliases old property names on the component object (`component.deferredActions` → `component.queuedUpdates`, `component.data` → `component.$wire`).

See `magewire-backwards-compatibility` for the full v2→v3 hook/event mapping table.

---

## Automatic enablement: `hyva-checkout-main`

Any component rendered inside the `hyva-checkout-main` layout container is BC-enabled automatically. This is the Hyvä Checkout-specific default — it exists because every v1 Hyvä Checkout component lived under that container, so blanket-enabling BC there gives the smoothest v1→v3 upgrade path for existing Hyvä Checkout installs.

To opt a component out (e.g. a new v3-native component living inside the checkout):

```php
#[HandleBackwardsCompatibility(enabled: false)]
class MyModernCheckoutComponent extends Component
{
}
```

To opt a component in outside the checkout, use the attribute without arguments, or set the flag programmatically via `store($component)->set('bc.enabled', true)` — see `magewire-backwards-compatibility`.

---

## Key Files

### PHP

| File | Purpose |
|------|---------|
| `themes/Hyva/Magewire/Features/SupportHyvaCheckoutBackwardsCompatibility/SupportHyvaCheckoutBackwardsCompatibility.php` | Hyvä-specific BC Feature — manages `memo.bc.enabled` and the `hyva-checkout-main` default |
| `themes/Hyva/Magewire/Features/SupportHyvaCheckoutBackwardsCompatibility/TemporaryHydrationRegistry.php` | Request-scoped hydration state used by the Feature |
| `themes/Hyva/etc/frontend/di.xml` | Feature registration |

### JavaScript (PHTML)

| File | Purpose |
|------|---------|
| `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/magewire-hooks.phtml` | Promise runner, deprecation warnings, hook replacement mapping |
| `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/magewire-events.phtml` | Deprecated event re-triggering, property aliases |
| `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/magewire-attributes.phtml` | wire:model directive migration |
| `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/magewire-components.phtml` | $wire proxy, entangle live default, component BC proxy |

---

## When migrating a Hyvä Checkout v1 component to v3

1. Follow the general migration checklist in `magewire-backwards-compatibility`.
2. Once the component uses v3 syntax end-to-end, add `#[HandleBackwardsCompatibility(enabled: false)]` to opt out of the `hyva-checkout-main` auto-default — otherwise the JS shims keep rewriting directives for a component that no longer needs them.
3. Eventually, when no components in the checkout rely on BC, the Feature can be removed from `themes/Hyva/etc/frontend/di.xml`.