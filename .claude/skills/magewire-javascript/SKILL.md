---
name: magewire-javascript
description: >
  Use this skill whenever writing JavaScript for Magento 2 themes that are not RequireJS-driven.
  This includes any frontend JS task involving Magewire, AlpineJS, or both together — such as building CSP-compatible components,
  enabling PHP-driven reactivity via Magewire, integrating AlpineJS with Magewire lifecycle hooks,
  or structuring reusable multi-theme JavaScript with clear separation of concerns.
  Always apply this skill as the default JS convention for non-RequireJS Magento 2 themes, regardless of complexity.
  Do NOT use for RequireJS-driven Magento 2 themes or server-side PHP Magewire component logic.
allowed-tools: ["Bash", "Read", "Write"]
---

# Magento 2 CSP-Compatible JavaScript (Magewire & AlpineJS)

This skill covers writing modern, CSP-compatible JavaScript for Magento 2 themes that are not RequireJS-driven.
It follows a Magewire-first convention that embraces PHP-driven reactivity, clean separation of concerns,
and deep AlpineJS integration — enabling components that are reusable across multiple themes while remaining
fully compliant with Magento 2's Content Security Policy requirements.

---

## Directory structure

All JavaScript lives in PHTML files under a `js/` subdirectory inside the view template tree. The view area determines
the root, and must always be chosen deliberately:

| Area | Path | When to use |
|---|---|---|
| `base` | `src/view/base/templates/js/` | Available in both frontend and adminhtml |
| `frontend` | `src/view/frontend/templates/js/` | Frontend themes only |
| `adminhtml` | `src/view/adminhtml/templates/js/` | Magento admin only |

Inside `js/`, files are grouped by JavaScript framework and then by category. The same structure is mirrored
across all view areas.

```
js/
├── alpinejs/                   # AlpineJS-specific code
│   ├── magewire.phtml          # Global — sits directly at the framework root
│   ├── components/             # Alpine components (x-data) — standalone only
│   │   └── magewire-notifier.phtml
│   └── directives/             # Custom Alpine directives (x-on, x-bind wrappers)
└── magewire/                   # Magewire-specific code
    ├── global.phtml            # Global — sits directly at the framework root
    ├── addons/                 # Reusable plain-JS APIs — standalone only
    │   └── notifier.phtml
    ├── directives/             # Custom Magewire directives (mage:*)
    │   ├── throttle.phtml
    │   └── notify.phtml
    ├── features/               # All JS belonging to a Support* feature
    │   └── support-magewire-loaders/
    │       └── support-magewire-loaders.phtml
    └── utilities/              # Single-responsibility helper functions
        ├── dom.phtml
        ├── loader.phtml
        └── str.phtml
```

**Rules:**
- Global code that applies to an entire framework goes directly at the framework root (e.g., `alpinejs/magewire.phtml`).
- Category-specific code goes in its named subfolder.
- Features always get their own subfolder named after the feature class, with a same-named PHTML inside.
- `addons/` and `alpinejs/components/` are for **standalone** components only — things reusable outside Magewire. If an addon or Alpine component belongs exclusively to a `Support*` feature, it lives inside that feature's folder instead.
- The directory structure under `js/` must be identical across all view areas.

---

## PHP boilerplate

Every PHTML file starts with the same PHP header. The fragment utility handles CSP nonce/hash injection
automatically — never write a raw `<script>` tag.

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

/** @internal Do not modify to ensure Magewire continues to function correctly. */
?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    // ... your JS here
</script>
<?php $script->end() ?>
```

- `$escaper` is always imported even when not used directly in this file, so subblocks can rely on it.
- The `@internal` docblock signals that this file is not intended for theme override.
- Never omit `$script->end()`. The fragment mechanism is only complete when `end()` is called.

**PHP values in JS strings** — always escape through `$escaper->escapeJs()`:

```php
'<?= $escaper->escapeJs(__('Too many requests! Please wait.')) ?>'
```

**PHP comments inside `<script>` blocks** — use PHP comment syntax, not JS comments:

```php
<?php /* This is an internal note that won't appear in the output. */ ?>
```

---

## Utilities

Utilities are single-responsibility helper functions with no dependencies on Magewire or Alpine.
They are plain JavaScript returned from a named function and registered on `window.MagewireUtilities`.

**Pattern:**

```javascript
function magewire{Name}Utility() {
    'use strict';

    return {
        methodName: function(arg) {
            // ...
        }
    }
}

<?php /* Register as Magewire utility. */ ?>
document.addEventListener('alpine:init', () => window.MagewireUtilities.register('name', magewire{Name}Utility), { once: true });
```

**Rules:**
- The function name is `magewire` + PascalCase name + `Utility`.
- `'use strict'` goes inside the function body, not outside.
- The function returns a plain object — no class, no prototype.
- Method names are short and clean. Prefer `parseText` over `parseInputTextString`.
- Registration uses `alpine:init` with `{ once: true }` to prevent double registration.
- The registration key (`'name'`) is the camelCase utility name, accessed later as `window.MagewireUtilities.name`.
- Utilities do not take Alpine reactive state — they are pure functions.

**Access from other files:**

```javascript
const text = window.MagewireUtilities.loader.parseText(value);
```

---

## Addons

Addons are reusable, framework-agnostic JavaScript APIs. They are the primary place for stateful logic,
event hooks, and async operations. Themes can consume an addon without knowing anything about Magewire internals.

**Pattern:**

```javascript
function magewire{Name}Addon() {
    'use strict';

    return {
        // Public state
        items: [],

        // Lifecycle hooks (arrays of callbacks)
        hooks: {
            onCreate: [],
            onTerminate: []
        },

        // Defaults
        defaults: {
            item: { state: 'idle', duration: null }
        },

        // API methods — short, clean names
        create: async function(data, options = {}) { /* ... */ },
        get:    function(id) { /* ... */ },
        remove: async function(id) { /* ... */ },

        // Internal trigger helper
        trigger: async function(hook, args = {}) { /* ... */ }
    };
}

<?php /* Register as Magewire addon. */ ?>
window.MagewireAddons.register('name', magewire{Name}Addon, true);
```

**Rules:**
- The function name is `magewire` + PascalCase name + `Addon`.
- `'use strict'` inside the function body.
- The third argument `true` to `register()` makes the result `Alpine.reactive()`. Use `true` whenever
  the addon holds state that needs to drive the DOM.
- Registration happens immediately (not inside an event listener). The `MagewireResource` class queues
  reactive registrations if Alpine is not yet ready and processes them on `alpine:init`.
- Addons can reference `window.Magewire` at call time, but must not depend on it being present at
  registration time.
- The public API should be flat and named clearly. Hide implementation details as closures or `const`
  variables inside the factory function, not as underscore-prefixed properties.

**Access from other files:**

```javascript
if (window.MagewireAddons.has('name')) {
    window.MagewireAddons.name.create(data);
}
```

---

## Alpine components

Alpine components are thin wrappers that expose an addon's state and a limited set of methods to the HTML template.
They exist solely to bridge the addon API with Alpine's `x-data` system in a CSP-compatible way.

Magewire ships `magewire.csp.min.js`, which is an unmodified direct copy of Livewire's JavaScript bundle.
Livewire's bundle includes the Alpine CSP build (`@alpinejs/csp`), which removes the dependency on `eval`
and `new Function` for expression evaluation. Most JavaScript expressions still work in HTML attributes.
The key difference is scope: only the component's own data properties and methods are in scope for HTML
attribute expressions. Closure variables declared inside the factory function (e.g.,
`const addon = window.MagewireAddons.poll`) are not accessible from HTML attributes — they must be exposed
as named methods on the returned object.

**Pattern:**

```javascript
function magewire{Name}() {
    'use strict';

    const addon = window.MagewireAddons.name;

    return {
        // Expose reactive addon state as a getter
        get items() {
            return addon.items;
        },

        <?php /* START: Only add those methods that should become CSP compatible. */ ?>
        remove: function() {
            addon.remove(this.item.id);
        },
        <?php /* END */ ?>

        // bindings: x-bind objects for elements that need multiple directives at once
        bindings: {
            item: {
                root: function() {
                    return {
                        'x-on:click'() { addon.remove(this.item.id); },
                        'x-bind:class'() { return ['item', `item--${this.item.type}`]; }
                    }
                }
            }
        }
    }
}

<?php /* Register as Alpine component. */ ?>
document.addEventListener('alpine:init', () => Alpine.data('magewire{Name}', magewire{Name}), { once: true });
```

**Rules:**
- The function name is `magewire` + PascalCase component name (no suffix).
- Expose addon state via `get` accessors so Alpine reactivity flows through.
- The `bindings` object holds `x-bind` definitions for complex element interactions.
  This lets templates use `x-bind="bindings.item.root()"` rather than inline Alpine expressions.
- Only methods that are called directly from HTML attributes belong inside the component.
  Pure internal helpers stay inside the addon.
- The PHP comment markers `START` / `END` delimit methods that exist to expose closure-variable logic
  (addon calls, utility calls) to HTML attributes. Without these wrappers, `@click="addon.stop()"` would
  fail because `addon` is a closure variable, not a component data property.
- Registration uses `alpine:init` with `{ once: true }`.
- If the component also exposes `Alpine.bind()` bindings, register a separate function:

```javascript
function magewire{Name}Bindings() {
    return {
        'x-bind:class'() { return 'my-component'; }
    };
}

document.addEventListener('alpine:init', () => Alpine.bind('magewire{Name}Bindings', magewire{Name}Bindings), { once: true });
```

**What Livewire's bundled Alpine CSP build forbids in HTML attribute expressions:**

| Forbidden | Why | Alternative |
|---|---|---|
| `` x-text="`Hello ${name}`" `` | Template literals | `x-text="'Hello ' + name"` |
| `@click="() => remove()"` | Arrow functions | `@click="remove()"` |
| `x-for="[k, v] in entries"` | Destructuring | Pre-compute `{k, v}` objects in data |
| `x-bind="{ ...defaults }"` | Spread operators | Return explicit object from method |
| `x-text="Object.keys(obj).length"` | Global functions | Expose via component method |
| `x-text="JSON.stringify(val)"` | Global functions | Expose via component method |
| `@click="user.name = 'John'"` | Nested property assignment | Expose via component method |
| `x-html="..."` | Blocked entirely | Restructure as text or component |

Everything else — ternaries, object literals, comparisons, arithmetic, string concatenation, negation,
simple assignments, `&&`/`||` — works fine in HTML attribute expressions.

Global functions not in scope include: `Object.entries`, `Object.keys`, `Object.values`, `Math.*`,
`JSON.*`, `parseInt`, `parseFloat`, `console.*`, `document.*`, `window.*`.

Pre-compute any data that would require global functions in templates. Store the result directly on
the data object:

```javascript
// In the factory function or when updating state:
propEntries: Object.keys(data).map(k => ({ k, v: data[k] }))

// In the template — simple iteration, no globals needed:
// x-for="p in propEntries" → x-text="p.k" / x-text="p.v"
```

---

## Directives

Directives are custom Alpine or Magewire directives registered via `Magewire.directive()`.
They add declarative HTML attributes that control element behavior.

**Pattern:**

```javascript
(() => {
    'use strict';

    document.addEventListener('magewire:initialized', event => {
        Magewire.directive('mage:{name}', ({ el, directive, component, cleanup }) => {
            const action = event => {
                // handle the event
            };

            el.addEventListener('click', action, { capture: true });

            cleanup(() => el.removeEventListener('click', action));
        });
    });
})();
```

**Rules:**
- Wrap in an IIFE `(() => { ... })()` — directives are self-contained and export nothing.
- `'use strict'` at the top of the IIFE.
- Listen on `magewire:initialized` (not `magewire:init` — directives require the full runtime to be ready).
- Magewire directive names use the `mage:` prefix: `mage:throttle`, `mage:notify`.
- Always call `cleanup()` to deregister event listeners and prevent memory leaks.
- Directive modifiers and expressions are read from the `directive` parameter:
  `directive.modifiers` (array of strings), `directive.expression` (string).

---

## Standalone components

A standalone component is a self-contained UI piece that can be used independently of Magewire. It consists
of three layers:

1. **Addon** (`js/magewire/addons/`) — the stateful JS API. Framework-agnostic, can run without Magewire.
2. **Alpine component** (`js/alpinejs/components/`) — thin wrapper exposing addon state and a minimal set of
   methods to HTML templates.
3. **UI template** (`magewire/ui-components/`) — the rendered HTML that consumes the Alpine component via
   `x-data`. Child templates go in a same-named subdirectory next to the main template.

```
src/view/base/templates/
├── js/
│   ├── alpinejs/components/magewire-{name}.phtml   ← Alpine component
│   └── magewire/addons/{name}.phtml                ← Addon
└── magewire/
    └── ui-components/
        ├── {name}.phtml                            ← UI template (x-data="magewire{Name}")
        └── {name}/
            └── {child}.phtml                       ← Child templates
```

The UI template is a plain Magento PHTML block registered in the `magewire.ui-components` layout container.
It uses `x-data="magewire{Name}"` and optionally `x-bind="magewire{Name}Bindings"`.

**When to use standalone vs. feature-owned:**

- Use the standalone pattern when the functionality should be usable independently — e.g. the notifier
  addon can be driven by anything (a custom JS call, a Magewire effect, a theme) without any Magewire
  component being on the page. `addons/` and `alpinejs/components/` are **reserved for this pattern only**.
- When an addon, Alpine component, or HTML template belongs exclusively to a `Support*` feature, it is
  **feature-owned** and must live inside that feature's folder, not in the global `addons/` or
  `alpinejs/components/` directories. See the Features section below.

A Magewire PHP feature can still *integrate* with a standalone component — it just pushes an effect that
the standalone component's JS layer reacts to. The two are independent; the feature is the bridge.

---

## Features

A feature's folder is the home for **all** JS and HTML that exclusively belongs to a `Support*` PHP
feature class. This includes the lifecycle bridge script, but also any addon, Alpine component, or HTML
template that only makes sense in the context of that feature.

**File placement:**

| What | Where |
|---|---|
| JS (bridge, addon, Alpine component) | `js/magewire/features/support-{name}/` |
| HTML template | `magewire/features/support-{name}/` |

The primary JS file is named after the feature class (`support-magewire-loaders.phtml`). Additional
sub-PHPTMLs within the same folder (e.g. `addon.phtml`, `component.phtml`) are fine when the feature
owns an addon or Alpine component.

**Layout registration:**
- The `magewire.features` container always has **one block per feature**. Any additional PHPTMLs (addon,
  Alpine component) are registered as **child blocks** of the primary feature block, not as siblings.
  The primary PHTML renders them in the correct order via `$block->getChildHtml('addon')` etc.
- HTML PHPTMLs → a single block in `magewire.after` (not `magewire.ui-components`, which is for
  standalone components only).

```xml
<block name="magewire.features.support-{name}"
       template="Magewirephp_Magewire::js/magewire/features/support-{name}/support-{name}.phtml"
>
    <block name="magewire.features.support-{name}.addon"
           as="addon"
           template="Magewirephp_Magewire::js/magewire/features/support-{name}/addon.phtml"
    />
    <block name="magewire.features.support-{name}.component"
           as="component"
           template="Magewirephp_Magewire::js/magewire/features/support-{name}/component.phtml"
    />
</block>
```

The primary PHTML renders children first, then its own bridge script:

```php
<?= $block->getChildHtml('addon') ?>
<?= $block->getChildHtml('component') ?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    // bridge JS
</script>
<?php $script->end() ?>
```

**Bridge pattern (the primary feature file):**

```javascript
document.addEventListener('magewire:init', function() {
    const addons = window.MagewireAddons;

    Magewire.hook('commit', function({ succeed }) {
        succeed(function({ effects }) {
            if (!addons.has('name') || !effects.myEffect) {
                return;
            }

            addons.name.update(effects.myEffect);
        });
    });
}, { once: true });
```

**Rules:**
- Feature scripts listen on `magewire:init` with `{ once: true }`.
- Always guard addon access with `addons.has('name')` — addons are optional.
- Read `window.MagewireAddons` and `window.MagewireUtilities` into local `const` at the top for clarity.
- No IIFE needed — the `magewire:init` listener already scopes the code.
- Feature-owned addons and Alpine components follow the exact same patterns as standalone ones — the
  only difference is their location on disk and which layout container their block uses.

---

## Event timing reference

| Event | When it fires | Use for |
|---|---|---|
| `alpine:init` | Alpine starts, before DOM walk | `Alpine.data()`, `Alpine.store()`, `Alpine.bind()`, utility/addon registration |
| `magewire:init` | Magewire runtime ready, Alpine initializing | Magewire hooks (`commit`, `request`), feature initialization |
| `magewire:initialized` | Full initialization complete | `Magewire.directive()` registration |

Always use `{ once: true }` on `addEventListener` calls for registration events to prevent
double-registration on navigations or re-hydrations.

---

## Layout XML registration

Every PHTML must have a corresponding block in the layout XML for the matching view area.

- `base` PHPTMLs → `src/view/base/layout/default.xml`
- `frontend` PHPTMLs → `src/view/frontend/layout/default.xml`
- `adminhtml` PHPTMLs → `src/view/adminhtml/layout/default.xml`

The available layout containers, in render order:

| Container | Purpose |
|---|---|
| `magewire.alpinejs` | Global Alpine code (stores, plugins) |
| `magewire.alpinejs.components` | `Alpine.data` component registrations |
| `magewire.utilities` | Utility registrations |
| `magewire.addons` | Addon registrations |
| `magewire.alpinejs.directives` | Custom Alpine directives (inside `magewire.before`) |
| `magewire.ui-components` | Alpine UI components (inside `magewire.before`) |
| `magewire.directives` | Magewire directive registrations (`mage:*`) |
| `magewire.features` | Feature JS |
| `magewire.after` | Everything else (HTML blocks, debug tools) |

The `view_model` argument is injected automatically by `SupportMagewireViewModel`. Do not add it manually
in layout XML blocks.

---

## CSP stylesheet rules

Inline `<style>` blocks and `style="..."` HTML attributes both require `style-src 'unsafe-inline'` and
must not be used.

- Move all styles to an external `.css` file under `src/view/{area}/web/css/`.
- Load the file via `<link rel="stylesheet" href="<?= $escaper->escapeUrl($block->getViewFileUrl('...')) ?>">`.
- Alpine's `:style` binding is safe — it sets styles via the JavaScript DOM API, which is not controlled
  by `style-src`.
- External same-origin CSS files are covered by the default `style-src 'self'` policy.
