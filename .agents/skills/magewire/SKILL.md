---
name: magewire
description: "Reference for Magewire — Magento 2's reactive component framework inspired by Laravel Livewire v3. Use when building, debugging, or explaining Magewire components, wire:* directives, lifecycle hooks, events, loading states, or any PHP/JS component API. Auto-load when working in a Magewire component class or PHTML template."
requires: magewire-portman
---

# Magewire

Magewire is a reactive component framework for Magento 2, heavily inspired by Laravel Livewire v3. It enables full-stack reactive UI without writing JavaScript — components are PHP classes that render PHTML templates, communicate with the server over AJAX, and re-render in place using Alpine.js as the reactive glue.

---

## Core Concept

A Magewire component is a PHP class that:
- Holds public properties as reactive state
- Exposes public methods callable from the frontend
- Renders a PHTML template
- Survives across requests via a serialized snapshot

When the user interacts with the UI (e.g. fills a form, clicks a button), the frontend sends the current snapshot + the pending updates/calls to `magewire/update`. The server hydrates the component, applies changes, re-renders, and returns the new HTML and snapshot. Alpine.js morphs the DOM in place.

---

## Creating a Component

```php
namespace Vendor\Module\Magewire;

use Magewirephp\Magewire\Component;

class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
}
```

Register it in layout XML (Magewire can be bound to any block class):

```xml
<block name="my.counter"
       template="Vendor_Module::counter.phtml">
    <arguments>
        <argument name="magewire" xsi:type="object">Vendor\Module\Magewire\Counter</argument>
    </arguments>
</block>
```

Template (`counter.phtml`):

```html
<div>
    <span><?= $magewire->count ?></span>
    <button wire:click="increment">+</button>
</div>
```

The component's root element must be a single HTML element. The `$magewire` variable is the component instance.

---

## Public Properties

Public properties are automatically serialized into the snapshot and are two-way bindable from the frontend.

```php
public string $name = '';
public int $quantity = 1;
public array $items = [];
```

Bind in template:

```html
<input wire:model="name" type="text">
<input wire:model.live="quantity" type="number">
```

- `wire:model` — binds on form submit / next request
- `wire:model.live` — binds on every change (triggers AJAX immediately)
- `wire:model.blur` — binds on blur

---

## Public Methods

Any public method can be called from the frontend:

```html
<button wire:click="save">Save</button>
```

Methods can also be triggered on other events:

```html
<input wire:keydown.enter="search">
<form wire:submit="submit">
```

---

## Lifecycle Hooks

All hooks are optional. Define them as public methods on your component:

| Hook | When it fires |
|------|--------------|
| `boot()` | Every request, before mount/hydrate |
| `booted()` | Every request, after boot/hydrate sequence |
| `initialize()` | Every request, before mount/hydrate |
| `mount(array $params)` | Initial render only |
| `hydrate()` | Every subsequent request (not initial) |
| `hydrateXxx()` | Hydrate for a specific property (e.g. `hydrateName`) |
| `updating($prop, $value)` | Before any property update |
| `updatingXxx($value)` | Before a specific property updates (e.g. `updatingName`) |
| `updated($prop, $value)` | After any property update |
| `updatedXxx($value)` | After a specific property updates |
| `rendering($view, $data)` | Before template is rendered |
| `rendered($view, $html)` | After template is rendered |
| `dehydrate()` | Before state is serialized back to snapshot |
| `dehydrateXxx()` | Dehydrate for specific property |
| `exception(\Throwable $e, callable $stopPropagation)` | On exception |

Dot-notation property hooks use studly case: `updatingFooBar` for `foo.bar` (dots are replaced, then the whole string is converted to StudlyCase).

---

## Skipping Lifecycle Phases

```php
public function mount(): void
{
    $this->skipRender();  // Don't re-render after this request
    $this->skipMount();   // Skip the mount phase
    $this->skipHydrate(); // Skip hydrate
}
```

---

## Events

Dispatch to other components on the same page:

```php
$this->dispatch('cart-updated', itemCount: 3);
```

Listen in another component:

```php
#[On('cart-updated')]
public function onCartUpdated(int $itemCount): void
{
    $this->count = $itemCount;
}
```

The `#[On]` attribute is the preferred listener API. A `protected $listeners = [...]` array on the component is also supported (legacy v1 style) but discouraged for new code.

Dispatch to self:

```php
$this->dispatch('refreshed')->self();
```

Dispatch to parent:

```php
$this->dispatch('saved')->up();
```

---

## Nested Components

Magewire supports parent-child component relationships. The child component is embedded as a block inside the parent's template. The parent can access children, and events can bubble up via `->up()`.

---

## Redirects

```php
public function save(): void
{
    // ... save logic
    $this->redirect('/success');
}
```

---

## Notifications

Magewire provides a fluent notification API via the `SupportMagewireNotifications` feature. Access it through the `magewireNotifications()` method on your component:

```php
// Create a notice (default type)
$this->magewireNotifications()->make(__('Item saved successfully'));

// Create with a specific type using fluent builders
$this->magewireNotifications()->make(__('Order placed'))->asSuccess();
$this->magewireNotifications()->make(__('Something went wrong'))->asError();
$this->magewireNotifications()->make(__('Check your input'))->asWarning();

// Full fluent API
$this->magewireNotifications()
    ->make(__('Order #123 confirmed'))
    ->asSuccess()
    ->withTitle(__('Order Confirmation'))
    ->withDuration(5000); // milliseconds (default: 3000)

// Named notifications (prevents duplicates with the same name)
$this->magewireNotifications()->make(__('Saving...'), 'save-progress');
```

Available type methods: `asSuccess()`, `asError()`, `asWarning()`, `asNotice()`, or `as(NotificationType $type)`.
Additional builders: `withTitle()`, `withoutTitle()`, `withDuration()`.

---

## Loading States

Wire directives control loading feedback automatically:

```html
<button wire:click="save" wire:loading.attr="disabled">Save</button>
<div wire:loading>Saving...</div>
<div wire:loading.remove>Ready</div>
```

Target specific actions:

```html
<div wire:loading wire:target="save">Saving...</div>
```

---

## Rate Limiting

The `SupportMagewireRateLimiting` feature provides configurable rate limiting for Magewire update requests. It is configured via Magento's admin system configuration and DI, not via PHP attributes on components.

---

## ViewModel Utilities

The `SupportMagewireViewModel` feature auto-injects a `MagewireViewModel` as `view_model` on every component block. Access it from your component via `magewireViewModel()`, or from templates via `$block->getData('view_model')`.

The view model provides access to a rich set of utilities via `utils()`:

```php
// From a component class
$this->magewireViewModel()->utils()->magewire();     // Magewire runtime access (mechanisms, config, update URI)
$this->magewireViewModel()->utils()->template();     // Template compilation utilities
$this->magewireViewModel()->utils()->layout();       // Layout information
$this->magewireViewModel()->utils()->fragment();     // CSP-compliant script fragment builder
$this->magewireViewModel()->utils()->security();     // CSRF token, security helpers
$this->magewireViewModel()->utils()->env();          // Environment mode checks (developer, production)
$this->magewireViewModel()->utils()->alpinejs();     // Alpine.js integration helpers
$this->magewireViewModel()->utils()->csp();          // CSP nonce generation
$this->magewireViewModel()->utils()->tailwind();     // Tailwind CSS utilities
$this->magewireViewModel()->utils()->application();  // Application-level utilities
```

From PHTML templates, the common pattern is:

```php
$magewireViewModel = $block->getData('view_model');
$magewireFragment  = $magewireViewModel->utils()->fragment();
```

---

## JavaScript Hooks

Access Magewire's JS lifecycle from custom scripts using the `magewire:init` event:

```javascript
document.addEventListener('magewire:init', event => {
    // Hook into every server roundtrip
    Magewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
        // Runs on every component update request
    });
});
```

Register a custom **addon** (reactive shared state, accessible as `Magewire.addons.myAddon`):

```javascript
document.addEventListener('magewire:init', () =>
    Magewire.addon('myAddon', function() {
        return { value: null, set(v) { this.value = v; } };
    }, true), // true wraps in Alpine.reactive()
    { once: true }
);
```

Register a custom **utility** (accessible as `MagewireUtilities.dom`):

```javascript
document.addEventListener('alpine:init', () =>
    window.MagewireUtilities.register('dom', function() {
        return { filterDataAttributes(el, prefix) { return {}; } };
    }),
    { once: true }
);
```

---

## Key Differences from Livewire v3

- Components are Magento **blocks**, not Laravel routes
- Uses **PHTML** templates, not Blade
- Request routing goes through **Magento's controller system** (`magewire/update`)
- CSRF uses Magento's **FormKey**, not Livewire's token
- Flash messages go through **Magento session**
- Layout and block management use **Magento's layout XML system**
- The PHP core is ported from Livewire via **Portman** (see `magewire-portman` skill)
- JavaScript is an **unmodified copy** of Livewire's JS bundle, served as a Magento static asset (kept untouched so Livewire upgrades can be adopted by replacing the file)
