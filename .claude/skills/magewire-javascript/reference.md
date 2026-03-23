# Reference

---

## Naming conventions

| Type | Function name | Example |
|---|---|---|
| Utility | `magewire{Name}Utility` | `magewireClipboardUtility` |
| Addon | `magewire{Name}Addon` | `magewirePollAddon` |
| Alpine component | `magewire{Name}` | `magewirePoll` |
| Alpine bindings | `magewire{Name}Bindings` | `magewirePollBindings` |
| Directive | — (IIFE, no export) | — |
| Feature | — (event listener, no export) | — |

Registration keys are the camelCase name without prefix or suffix: `'clipboard'`, `'poll'`, `'notifier'`.

---

## Registration calls

```javascript
// Utility — waits for alpine:init
document.addEventListener('alpine:init', () => window.MagewireUtilities.register('name', factoryFn), { once: true });

// Addon — immediate; MagewireResource queues reactive items if Alpine is not yet ready
window.MagewireAddons.register('name', factoryFn, true);   // true = Alpine.reactive
window.MagewireAddons.register('name', factoryFn, false);  // false = plain object

// Alpine component
document.addEventListener('alpine:init', () => Alpine.data('magewire{Name}', factoryFn), { once: true });

// Alpine bindings (optional companion)
document.addEventListener('alpine:init', () => Alpine.bind('magewire{Name}Bindings', bindingsFn), { once: true });

// Alpine store
document.addEventListener('alpine:init', () => Alpine.store('name', { ... }));

// Directive
document.addEventListener('magewire:initialized', event => Magewire.directive('mage:name', handler));
```

---

## Event timing

| Event | Fires when | Register here |
|---|---|---|
| `alpine:init` | Alpine starts, before DOM walk | `Alpine.data`, `Alpine.store`, `Alpine.bind`, utilities, addons (if not immediate) |
| `magewire:init` | Magewire runtime ready | `Magewire.hook('commit')`, `Magewire.hook('request')`, feature init |
| `magewire:initialized` | Full init complete | `Magewire.directive()` |

Always add `{ once: true }` to registration listeners.

---

## PHP boilerplate

**Minimal (no PHP values in JS):**

```php
<?php

declare(strict_types=1);

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

/** @var Escaper $escaper */
/** @var Template $block */
/** @var MagewireViewModel $magewireViewModel */

$magewireViewModel = $block->getData('view_model');
$magewireFragment  = $magewireViewModel->utils()->fragment();
?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    // ...
</script>
<?php $script->end() ?>
```

**With PHP enum values:**

```php
use Magewirephp\Magewire\Model\Magewire\Notifier\NotificationStateEnum;

$state = NotificationStateEnum::IDLE->getState(); // used inside escapeJs()
```

```javascript
const state = '<?= $escaper->escapeJs(NotificationStateEnum::IDLE->getState()) ?>';
const levels = JSON.parse('<?= json_encode(NotificationStateEnum::cases()) ?>');
```

**PHP comment inside `<script>`:**

```php
<?php /* This comment does not appear in rendered output. */ ?>
```

---

## Alpine CSP expression rules

`magewire.csp.min.js` is an unmodified direct copy of Livewire's JavaScript bundle. Livewire's bundle
includes the Alpine CSP build (`@alpinejs/csp`), which removes the dependency on `eval` and `new Function`.
Only a minimal subset of expressions is safe in HTML attributes — when in doubt, move logic into a component method.

**Allowed in HTML attribute expressions:**

| Pattern | Example |
|---|---|
| Property access | `x-text="item.name"` |
| Method reference (no parens) | `@click="remove"`, `x-show="isOpen"` |
| Simple property assignment | `@click="open = false"` |
| Object literal | `:class="{'active': isOpen}"` |
| `x-for` iteration | `x-for="item in items"` |
| `x-bind` plain object property | `x-bind="bindings.root"` |

**Forbidden in HTML attribute expressions:**

| Pattern | Example | Alternative |
|---|---|---|
| Method calls with parens | `@click="remove()"`, `x-show="isOpen()"` | `@click="remove"`, `x-show="isOpen"` |
| Method args in HTML | `@click="selectItem(123)"` | `@click="selectItem" data-item-id="123"` (use `this.$el.dataset`) |
| Loop var as method arg | `@click="expand(entry)"` | `@click="expand"` — access via `this.entry` inside method |
| Template literals | `` x-text="`Hi ${name}`" `` | Use a component method |
| Arrow functions | `@click="() => remove()"` | `@click="remove"` |
| Destructuring | `x-for="[k, v] in entries"` | Pre-compute `{k, v}` objects |
| Spread operators | `x-bind="{ ...obj }"` | Return explicit object from method |
| Function call in `x-bind` | `x-bind="bindings.root()"` | `x-bind="bindings.root"` (plain object property) |
| Comparison / equality | `x-show="count > 0"`, `x-show="a === b"` | `x-show="hasEntries"` (method) |
| Ternary | `x-text="a ? 'x' : 'y'"` | Use a component method |
| Arithmetic / string concat | `x-text="count + 1"` | Use a component method |
| Negation / logical operators | `x-show="!loading"`, `x-show="a && b"` | Use a component method |
| Global functions | `x-text="Object.keys(o).length"` | Expose count via component method |
| Nested property assignment | `@click="user.name = 'val'"` | Expose via component method |
| `x-model` | `x-model="value"` | `:value="value"` + `@input="setValue"` |
| Range `x-for` | `x-for="i in 10"` | Pre-compute an array |
| `x-html` | Any | Restructure as text content |

Globals not in scope: `Object.*`, `Math.*`, `JSON.*`, `parseInt`, `parseFloat`, `console.*`,
`document.*`, `window.*`.

**Loop variable scope:** variables from `x-for="entry in entries"` are accessible inside methods
as `this.entry`. Never pass them as arguments from HTML:

```javascript
// ✗ fails — method args in HTML not supported
// @click="toggleExpand(entry)"

// ✓ works — access loop var via this inside the method
toggleExpand: function() { this.entry.expanded = !this.entry.expanded; }
// @click="toggleExpand"
```

**Closure variable scope:** closure variables inside the factory function are not accessible from HTML.
Expose them as component methods:

```javascript
// ✗ fails — addon is a closure variable, not a component property
// @click="addon.stop()"

// ✓ works — stop is a property of the returned object
stop: function() { addon.stop(); }
// @click="stop"
```

Pre-compute anything that would require global functions in templates:

```javascript
// In factory function or when updating state:
propEntries: Object.keys(data).map(k => ({ k, v: data[k] }))

// In template — no globals needed:
// x-for="p in propEntries" → x-text="p.k" / x-text="p.v"
```

---

## CSP stylesheet rules

| Pattern | Safe | Notes |
|---|---|---|
| `<link rel="stylesheet" href="...">` | Yes | Same-origin covered by `style-src 'self'` |
| External `.css` file via `getViewFileUrl()` | Yes | Standard Magento static file resolution |
| Alpine `:style` binding | Yes | Set via JS DOM API, not blocked by `style-src` |
| `<style>` block | **No** | Requires `style-src 'unsafe-inline'` |
| `style="..."` HTML attribute | **No** | Requires `style-src 'unsafe-inline'` |

**Loading an external stylesheet from PHTML:**

```php
<link rel="stylesheet" href="<?= $escaper->escapeUrl($block->getViewFileUrl('Magewirephp_Magewire::css/my-file.css')) ?>">
```

CSS files live at `src/view/{area}/web/css/`.

---

## Script CSP

The `$magewireFragment->make()->script()->start()` / `$script->end()` pattern handles all cases automatically:

| Page cache state | CSP method used |
|---|---|
| FPC disabled or layout not cacheable | `nonce="..."` attribute on `<script>` |
| FPC enabled and layout cacheable | SHA hash registered with `DynamicCspCollector` |

Never write a raw `<script>` tag in Magewire PHTML files.

---

## Addon access patterns

```javascript
// Check before use (addons are optional)
if (window.MagewireAddons.has('name')) {
    window.MagewireAddons.name.method();
}

// Direct access when presence is guaranteed (e.g., inside the component wrapping it)
const addon = window.MagewireAddons.name;

// Utility access
const result = window.MagewireUtilities.name.method(arg);
```

---

## Directory structure

```
src/view/
├── base/
│   ├── layout/default.xml
│   ├── templates/
│   │   ├── js/                                     ← JS-only PHTML files
│   │   │   ├── alpinejs/
│   │   │   │   ├── {global}.phtml                  ← sits at framework root
│   │   │   │   ├── components/                     ← standalone Alpine components only
│   │   │   │   │   └── magewire-{name}.phtml
│   │   │   │   └── directives/                     ← reserved for custom Alpine directives
│   │   │   └── magewire/
│   │   │       ├── {global}.phtml                  ← sits at framework root
│   │   │       ├── addons/                         ← standalone addons only
│   │   │       │   └── {name}.phtml
│   │   │       ├── directives/
│   │   │       │   └── {name}.phtml
│   │   │       ├── features/                       ← all JS owned by a Support* feature
│   │   │       │   └── support-{name}/
│   │   │       │       ├── support-{name}.phtml    ← primary bridge file
│   │   │       │       ├── addon.phtml             ← feature-owned addon (if any)
│   │   │       │       └── component.phtml         ← feature-owned Alpine component (if any)
│   │   │       └── utilities/
│   │   │           └── {name}.phtml
│   │   └── magewire/                               ← Non-JS PHTML templates
│   │       ├── ui-components/                      ← standalone UI templates only
│   │       │   ├── {name}.phtml
│   │       │   └── {name}/
│   │       │       └── {child}.phtml
│   │       └── features/                           ← HTML templates owned by a Support* feature
│   │           └── support-{name}/
│   │               └── {name}.phtml
│   └── web/css/
│       └── {name}.css
├── frontend/
│   ├── layout/default.xml
│   └── templates/                  ← same structure as base/templates/
└── adminhtml/
    ├── layout/default.xml
    └── templates/                  ← same structure as base/templates/
```

---

## Layout container reference

Containers render in this order:

| Container | Content |
|---|---|
| `magewire.global.before` | Parent of `magewire.alpinejs.load`, `magewire.alpinejs`, `magewire.alpinejs.components` |
| `magewire.alpinejs` | Global Alpine code (stores, plugins) — before Magewire's Alpine code |
| `magewire.alpinejs.components` | `Alpine.data` component registrations |
| `magewire.utilities` | Utility registrations |
| `magewire.addons` | Addon registrations |
| `magewire.global.after` | Custom extensions after the global block |
| `magewire.before` | Parent of `magewire.alpinejs.directives`, `magewire.ui-components`, `magewire.alpinejs.after` |
| `magewire.alpinejs.directives` | Custom Alpine directives (inside `magewire.before`) |
| `magewire.ui-components` | Alpine UI components (inside `magewire.before`) |
| `magewire.alpinejs.after` | Custom Alpine code after Magewire's code (inside `magewire.before`) |
| `magewire.before.internal` | Internal state blocks |
| `magewire.internal` | Core internal scripts — do not override |
| `magewire.directives` | Magewire directive registrations (`mage:*`) |
| `magewire.features` | Feature JS |
| `magewire.after.internal` | Post-internal extensions |
| `magewire.disabled` | Renders only when Magewire is inactive |
| `magewire.after` | Everything else (HTML blocks, debug tools) |
| `magewire.legacy` | V1 backwards compatibility — do not use for new code |

The `view_model` argument on layout blocks is injected automatically by `SupportMagewireViewModel`.
Do not add it in layout XML.

---

## Per-type checklist

**Utility**
- [ ] Function named `magewire{Name}Utility`
- [ ] `'use strict'` inside the function body
- [ ] Returns a plain object with named methods
- [ ] Registered via `MagewireUtilities.register()` inside `alpine:init` with `{ once: true }`
- [ ] Block placed in `magewire.utilities` container

**Addon**
- [ ] Function named `magewire{Name}Addon`
- [ ] `'use strict'` inside the function body
- [ ] Returns a full API object (state + methods)
- [ ] Registered via `MagewireAddons.register()` immediately (not inside listener)
- [ ] Third argument `true` if state needs to be Alpine-reactive
- [ ] Block placed in `magewire.addons` container

**Alpine component**
- [ ] Function named `magewire{Name}` (no suffix)
- [ ] `'use strict'` inside the function body
- [ ] Reads addon via `const addon = window.MagewireAddons.name`
- [ ] Exposes state via `get` accessors
- [ ] Only methods called from HTML templates included (CSP comment markers)
- [ ] Complex element bindings in `bindings` object
- [ ] Registered via `Alpine.data()` inside `alpine:init` with `{ once: true }`
- [ ] Block placed in `magewire.alpinejs.components` container
- [ ] No template literals, arrow functions, destructuring, spread, or global functions in HTML attributes
- [ ] No operators in HTML attributes (comparisons, ternary, negation, arithmetic) — use methods instead
- [ ] Methods referenced without parentheses in HTML: `@click="toggle"`, `x-show="isOpen"` (never `toggle()`)
- [ ] No method arguments passed from HTML — loop variables accessed via `this.varName` inside methods
- [ ] Closure variables (addon, utility references) exposed as component methods — not called directly from HTML

**Directive**
- [ ] Wrapped in IIFE `(() => { 'use strict'; ... })()`
- [ ] Registered inside `magewire:initialized` listener
- [ ] Name uses `mage:` prefix
- [ ] All event listeners removed via `cleanup()`
- [ ] Block placed in `magewire.directives` container

**Feature (JS bridge)**
- [ ] Listens on `magewire:init` with `{ once: true }`
- [ ] Reads `window.MagewireAddons` and `window.MagewireUtilities` into local `const`
- [ ] Guards addon access with `addons.has('name')`
- [ ] Primary file named after the PHP Feature class, inside the feature's folder
- [ ] Block placed in `magewire.features` container

**Feature-owned addon / Alpine component**
- [ ] Lives in `js/magewire/features/support-{name}/` — NOT in `addons/` or `alpinejs/components/`
- [ ] Follows same patterns as standalone addon/Alpine component
- [ ] Registered as **child blocks** of the primary feature block (with `as="addon"`, `as="component"`)
- [ ] Primary PHTML renders them via `$block->getChildHtml('addon')`, `$block->getChildHtml('component')` — in that order, before the bridge script
- [ ] Only ONE block for the feature sits directly in `magewire.features` — never sibling blocks

**Feature-owned HTML template**
- [ ] Lives at `templates/magewire/features/support-{name}/{name}.phtml`
- [ ] Block placed in `magewire.after` container — NOT `magewire.ui-components`

**Standalone component (UI template)**
- [ ] Addon registered in `magewire.addons`, Alpine component in `magewire.alpinejs.components`
- [ ] UI template at `templates/magewire/ui-components/{name}.phtml`
- [ ] Child templates in `templates/magewire/ui-components/{name}/`
- [ ] Uses `x-data="magewire{Name}"` (and optionally `x-bind="magewire{Name}Bindings"`)
- [ ] Block placed in `magewire.ui-components` container
- [ ] Justified as standalone: the addon is usable without a Magewire component on the page
