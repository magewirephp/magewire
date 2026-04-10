---
name: magewire-backwards-compatibility
description: >
  Reference for Magewire's backwards compatibility system. Use when migrating
  Magewire v1 (Livewire v2) code to v3, enabling BC for existing components,
  understanding wire:model / entangle behavioral changes, or working with the
  BC memo flag, JS hooks, event mappings, and component proxying.
  Triggers for: BC migration, wire:model.defer, wire:model.lazy, entangle .live,
  deprecated hooks, Hyvä Checkout v1 components, #[HandleBackwardsCompatibility].
license: MIT
metadata:
  author: Willem Poortman
---

# Magewire Backwards Compatibility

Magewire v3 is built on Livewire v3, which introduced breaking changes from v2. The BC system allows existing v1 components (particularly those built for Hyvä Checkout) to keep working without code changes while providing a migration path to v3 conventions.

---

## The Two BC Layers

### 1. Core BC (`lib/MagewireBc/`)

Framework-level compatibility that applies to all Magewire components regardless of theme.

**PHP trait** — `HandlesComponentBackwardsCompatibility` is mixed into the base `Component` class. It provides deprecated v1 APIs: `$this->id` (public), `getPublicProperties()`, and concerns for Emit, Error, BrowserEvent, and Request. The `Component\Form` base class uses this trait via `HandlesFormComponentBackwardsCompatibility` (a deprecated pass-through that exists for potential form-specific BC, currently empty).

**Feature** — `SupportMagewireBackwardsCompatibility` (sort order 99100):
- Pushes BC effects into the snapshot during `dehydrate()`: property path mappings (`data` → `$wire`, `__livewire` → `queuedUpdates`) and preferences
- These effects are read by JS to proxy old property access patterns

### 2. Hyvä Checkout BC (`themes/Hyva/`)

Theme-specific compatibility for components living inside the Hyvä Checkout layout. This is where the wire directive and entangle migrations happen.

**Feature** — `SupportHyvaCheckoutBackwardsCompatibility`:
- Pushes `memo.bc.enabled` flag into the snapshot during `dehydrate()`
- Flag resolution (priority order):
  1. `#[HandleBackwardsCompatibility]` attribute on the component class
  2. Previously hydrated value from the component's data store
  3. Whether the component lives inside the `hyva-checkout-main` layout container
- On `hydrate()`, restores the flag from memo into the component's data store

**JS templates** — four PHTML files in `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/`:

| File | Purpose |
|------|---------|
| `magewire-hooks.phtml` | Promise-based hook runner, deprecation warnings, replacement mapping |
| `magewire-events.phtml` | Maps deprecated event names to v3 hooks, proxies component properties |
| `magewire-attributes.phtml` | Migrates wire:model directives on BC-enabled components |
| `magewire-components.phtml` | Proxies `Magewire.find()` and `$wire.entangle()` for BC components |

---

## Wire Directive Changes (v2 → v3)

| Livewire v2 | Livewire v3 | Behavior |
|---|---|---|
| `wire:model` | `wire:model.live` | Syncs on every input change (instant) |
| `wire:model.defer` | `wire:model` | Syncs on form submit / next request (now the default) |
| `wire:model.lazy` | `wire:model.blur` | Syncs when the field loses focus |

### How BC handles this (`magewire-attributes.phtml`)

For components with `memo.bc.enabled`, the JS intercepts `element.init` and `morph.updating` hooks and migrates attributes. Each transformation runs independently:

```javascript
// wire:model → wire:model.live (v2 instant → v3 live)
// wire:model.defer → wire:model (v2 deferred → v3 default)
// wire:model.lazy → wire:model.blur (v2 lazy → v3 blur)
```

### Migrating your own code

Remove the old modifiers and use v3 syntax:

```html
<!-- Before (v1/v2) -->
<input wire:model="name">              <!-- instant sync -->
<input wire:model.defer="email">       <!-- deferred -->
<input wire:model.lazy="phone">        <!-- on blur -->

<!-- After (v3) -->
<input wire:model.live="name">         <!-- instant sync (opt-in) -->
<input wire:model="email">             <!-- deferred (now default) -->
<input wire:model.blur="phone">        <!-- on blur -->
```

---

## Entangle Changes (v2 → v3)

| Livewire v2 | Livewire v3 |
|---|---|
| `$wire.entangle('prop')` — **live** by default | `$wire.entangle('prop')` — **deferred** by default |
| N/A | `$wire.entangle('prop').live` — opt-in to live |

### How BC handles this (`magewire-components.phtml`)

The `makeComponentBackwardsCompatible()` function wraps `component.$wire` in a Proxy. When `entangle` or `$entangle` is accessed, the returned function's `live` parameter defaults to `true` instead of `false`:

```javascript
// For BC components, this:
this.$wire.entangle('couponCode')
// behaves like:
this.$wire.entangle('couponCode').live
```

The wire proxy is created once per component and cached on `component.__bcWire`.

### Migrating your own code

If you want instant sync (v2 default), add `.live` explicitly:

```javascript
// Before (v1/v2) — live by default
couponCode: this.$wire.entangle('couponCode'),

// After (v3) — must opt in to live
couponCode: this.$wire.entangle('couponCode').live,

// Or keep deferred (v3 default) — no .live needed
couponCode: this.$wire.entangle('couponCode'),
```

---

## Hook / Event Changes (v2 → v3)

| Deprecated (v2) | Replacement (v3) |
|---|---|
| `component.initialized` | `component.init` |
| `element.initialized` | `element.init` |
| `element.updating` | `morph.updating` |
| `element.removed` | `morph.removed` |
| `message.sent` | `commit` |
| `message.failed` | `commit` → `fail()` |
| `message.received` | `commit` → `succeed()` |
| `message.processed` | `commit` → `succeed()` → `queueMicrotask` |

### How BC handles this (`magewire-hooks.phtml` + `magewire-events.phtml`)

- `magewire-hooks.phtml` defines `runMagewireBackwardsCompatibleHook()` — a promise-based runner that waits for `magewire:available` before executing. In debug mode, logs deprecation warnings with the replacement hook name.
- `magewire-events.phtml` hooks into each v3 event and re-triggers the deprecated v2 event name via `Magewire.trigger()`.

### Component property aliases (`magewire-events.phtml`)

Old property names are aliased on the component object:

```javascript
// component.deferredActions → component.queuedUpdates
// component.data → component.$wire
```

---

## Component Proxy (`magewire-components.phtml`)

`Magewire.find(id)` is wrapped to return a Proxy. Accessing `.__instance` returns a BC-proxied component via `makeComponentBackwardsCompatible()`.

The BC component proxy intercepts property access to:
1. Return a cached `$wire` proxy with live-by-default entangle
2. Resolve BC effect mappings (e.g., `component.data` → `component.$wire` via `effects.bc.map`)
3. Support path-based resolution (`path:property.nested.value`)

---

## Enabling BC for a Component

### Automatic (Hyvä Checkout)

Any component rendered inside the `hyva-checkout-main` layout container is automatically BC-enabled. No code changes needed.

### Explicit via PHP attribute

```php
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;

#[HandleBackwardsCompatibility]
class MyLegacyComponent extends Component
{
    // This component gets BC behavior regardless of layout position
}

#[HandleBackwardsCompatibility(enabled: false)]
class MyModernComponent extends Component
{
    // Explicitly opt out, even if inside hyva-checkout-main
}
```

### Via data store (programmatic)

```php
use function Magewirephp\Magewire\store;

// In a Feature or hook:
store($component)->set('bc.enabled', true);
```

---

## Key Files

### PHP

| File | Purpose |
|------|---------|
| `lib/MagewireBc/Features/SupportMagewireBackwardsCompatibility/SupportMagewireBackwardsCompatibility.php` | Core BC feature — pushes path mappings into effects |
| `lib/MagewireBc/Features/SupportMagewireBackwardsCompatibility/HandleBackwardsCompatibility.php` | PHP attribute for explicit BC opt-in/out |
| `lib/MagewireBc/Features/SupportMagewireBackwardsCompatibility/HandlesComponentBackwardsCompatibility.php` | Trait with deprecated v1 component APIs |
| `themes/Hyva/Magewire/Features/SupportHyvaCheckoutBackwardsCompatibility/SupportHyvaCheckoutBackwardsCompatibility.php` | Hyvä-specific BC — manages `memo.bc.enabled` flag |

### JavaScript (PHTML)

| File | Purpose |
|------|---------|
| `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/magewire-hooks.phtml` | Promise runner, deprecation warnings, hook replacement mapping |
| `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/magewire-events.phtml` | Deprecated event re-triggering, property aliases |
| `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/magewire-attributes.phtml` | wire:model directive migration |
| `themes/Hyva/view/frontend/templates/magewire-features/support-hyva-checkout-backwards-compatibility/magewire-components.phtml` | $wire proxy, entangle live default, component BC proxy |

---

## Migration Checklist

When upgrading a v1 component to v3:

1. **wire:model** — replace `wire:model` with `wire:model.live` if you need instant sync, remove `.defer` (it's now the default), replace `.lazy` with `.blur`
2. **entangle** — add `.live` if you need instant sync, otherwise leave as-is (deferred is now default)
3. **JS hooks** — replace deprecated event names (see table above)
4. **Component properties** — replace `component.data` with `component.$wire`, `component.deferredActions` with `component.queuedUpdates`
5. **PHP APIs** — replace `$this->getPublicProperties()` with `$this->all()`, avoid public `$this->id` (use `$this->id()` or `$this->getId()`)
6. **Remove BC attribute** — once migrated, remove `#[HandleBackwardsCompatibility]` if present
