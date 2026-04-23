# Upgrading to Magewire 3.0

Magewire 3 is a full rewrite. Its core is now a port of [Laravel Livewire v3](https://livewire.laravel.com/docs/upgrading), layered onto a Magento-native Mechanisms and Features pipeline. If you're coming from Magewire 1.x, or from Laravel Livewire, this guide covers only the essentials — enough to get an existing component booting on 3.0. For the full reference, see the [docs](https://magewirephp.github.io/magewire-docs/) and the `magewire-backwards-compatibility` skill.

## Audience

- **Magewire 1.x users** — existing Magento 2 + Magewire projects. Read the whole document.
- **Livewire-aware readers** — familiar with Livewire v2/v3 but new to Magewire. Skip to [Differences from upstream Livewire](#differences-from-upstream-livewire).

## Requirements

| | 1.x | 3.0 |
|---|---|---|
| PHP | `>=7.4` | `>=8.2` |
| Magento | any 2.x | any 2.x |
| Livewire core | none (hand-written) | ported from `livewire/livewire:~3.7.11` |

## Composer

Update the constraint:

```diff
- "magewirephp/magewire": "^1.13"
+ "magewirephp/magewire": "^3.0"
```

Two dependency changes happen automatically:

- **`rakit/validation` → `magewirephp/validation`** — drop-in fork, keeps the `Rakit\Validation\` namespace. If you `require rakit/validation` directly in your own `composer.json`, remove it; the fork declares `replace`.
- **`magento/framework`** is no longer listed in `require`. Your root project should already constrain Magento — Magewire no longer enforces a floor.

After updating, run:

```bash
composer update magewirephp/magewire --with-all-dependencies
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Turn on backwards compatibility

Most v1 components keep working under a BC layer. Opt in per-component:

```php
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\Attributes\HandleBackwardsCompatibility;

#[HandleBackwardsCompatibility]
class MyComponent extends \Magewirephp\Magewire\Component
{
    // v1-style code keeps working
}
```

The attribute flips a `bc` memo flag in the component snapshot. Without it, new v3 semantics apply. See the `magewire-backwards-compatibility` skill for the full behavior matrix.

## Breaking changes you will hit first

### `wire:model` is no longer live by default

Livewire v3 changed `wire:model` to defer updates until an explicit action. For v1 behavior, use `wire:model.live`:

```diff
- <input wire:model="query" />
+ <input wire:model.live="query" />
```

The BC attribute above maps plain `wire:model` back to live semantics for tagged components.

### Entangle uses `.live` / `.blur` modifiers

`@entangle('prop')` now defers; `@entangle('prop').live` matches v1 behavior. BC attribute covers the default case.

### Component lookup

Component classes are resolved via layout XML blocks by the `LayoutResolver` (pluggable via `ComponentResolverManager`). If you used internal APIs to look up components manually in v1, migrate to injecting the resolver.

### PHTML directives

Templates now flow through a compiler. Preferred:

- `@json`, `@if`, `@foreach`, `@script`, `@fragment`, `@slot`, `@template`, `@translate`, `@child`, `@auth`, `@guest`
- `@escape.url`, `@escape.attr`, `@escape.js`, `@escape.html`, `@escape.css`
- `@render.parent`, `@render.child`

Raw PHP in PHTML still works. Raw `<script>` tags are discouraged — use `$magewireFragment->make()->script()->start()/end()` for CSP-safe inline JS.

### Removed / renamed

- `LivewireManager` → `MagewireManager`.
- v1's bespoke runtime, hydration, and update flow are gone — replaced by Livewire-style snapshots (data + memo + checksum).
- Synthesizers replace ad-hoc property casting. Built-ins: `DataObjectSynth`, `ArraySynth`, `EnumSynth`, `FloatSynth`, `IntSynth`, `StdClassSynth`.

## Differences from upstream Livewire

If you know Livewire v3, these are the Magewire-specific bits:

- **No `Livewire::component()` registration.** Components are discovered through Magento layout XML — `<referenceContainer><block class="..."/></referenceContainer>` — and resolved by `ResolveComponents`.
- **Service registration via DI, not a service provider.** Mechanisms and Features are registered in **area-scoped** DI (`etc/frontend/di.xml` or `etc/adminhtml/di.xml`), never global `etc/di.xml`.
- **`app()` bridge.** Livewire code that calls `app('livewire')` or `app('redirect')` works — `Containers` maps those lookups to Magento DI bindings.
- **Update endpoint.** POSTs go to `magewire/update` (registered as a custom router at sort order 5), not `/livewire/update`.
- **Template compiler is opt-in per directive area** — extensible via DI virtualTypes, so themes can add their own directive namespaces.
- **Theme compatibility modules.** Hyvä, Luma, Breeze, and Magento Admin each have their own module under `themes/`, registered via `composer.json`'s `autoload.files`.

## Verifying the upgrade

1. `composer update` resolves cleanly.
2. `bin/magento setup:di:compile` succeeds.
3. Load a page with a Magewire component — initial render works (`PRECEDING` mode).
4. Trigger an action — AJAX update works (`SUBSEQUENT` mode, POST to `magewire/update`).
5. If a v1 component breaks, add `#[HandleBackwardsCompatibility]` and re-test.

## Still stuck?

- [Discussions](https://github.com/magewirephp/magewire/discussions) — questions and migration help.
- [Docs](https://magewirephp.github.io/magewire-docs/) — full API reference.
- Upstream Livewire v3 [upgrade guide](https://livewire.laravel.com/docs/upgrading) — for semantics not changed by Magewire.