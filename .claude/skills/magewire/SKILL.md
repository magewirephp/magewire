---
name: magewire
description: Reference for Magewire — Magento 2's reactive component framework inspired by Laravel Livewire v3. Use when building, debugging, or explaining Magewire components, wire:* directives, lifecycle hooks, events, loading states, or any PHP/JS component API. Auto-load when working in a Magewire component class or PHTML template.
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

Register it in layout XML:

```xml
<block class="Magewirephp\Magewire\Block\Magewire"
       name="my.counter"
       template="Vendor_Module::counter.phtml">
    <arguments>
        <argument name="data" xsi:type="array">
            <item name="magewire" xsi:type="string">Vendor\Module\Magewire\Counter</item>
        </argument>
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

**Computed properties** — use a getter, not stored in snapshot:

```php
public function getFullNameProperty(): string
{
    return $this->firstName . ' ' . $this->lastName;
}
```

Access in PHTML template as `$magewire->fullName` or `$this->fullName` in the PHP component class.

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

Dot-notation property hooks use underscores: `updatingFoo_Bar` for `foo.bar`.

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

Or via a layout-level listener:

```php
protected $listeners = ['cart-updated' => 'onCartUpdated'];
```

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

Use the built-in notifier (Magewire-specific):

```php
$this->notify('Item saved successfully');
$this->notify('Something went wrong', 'error');
```

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

Use the `#[Throttle]` attribute or enable via `SupportMagewireRateLimiting` feature on a per-component basis to limit how fast the frontend can trigger updates.

---

## ViewModel Utilities

Magewire auto-injects a `MagewireViewModel` as `view_model` on every component block. It exposes utilities accessible from the component:

```php
$this->utils()->magewire();    // Magewire utilities
$this->utils()->template();    // Template utilities
$this->utils()->layout();      // Layout utilities
$this->utils()->fragment();    // Fragment utilities
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

Register a custom **utility** (accessible as `Magewire.utilities.dom`):

```javascript
document.addEventListener('magewire:init', () =>
    Magewire.utility('dom', function() {
        return { filterDataAttributes(el, prefix) { return {}; } };
    })
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
