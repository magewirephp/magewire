# JavaScript Integration

## CSP-Compatible Scripts Only

All inline JavaScript must use the fragment utility (`$magewireFragment->make()->script()->start()/end()`). Never write raw `<script>` tags — they violate Magento's Content Security Policy. The fragment mechanism handles CSP nonce/hash injection automatically.

For the full PHP boilerplate and the reasons behind each step, see the `magewire-javascript` skill.

## Use the Correct Event for Registration

| Event | When | Use for |
|-------|------|---------|
| `alpine:init` | Alpine starts, before DOM walk | `Alpine.data()`, `Alpine.store()`, `Alpine.bind()`, utility registration |
| `magewire:init` | Magewire runtime ready | Magewire hooks (`commit`, `request`), feature bridge scripts |
| `magewire:initialized` | Full initialization complete | `Magewire.directive()` registration |

```javascript
// Alpine component registration
document.addEventListener('alpine:init', () => Alpine.data('myComponent', myComponent), { once: true });

// Magewire hook
document.addEventListener('magewire:init', function() {
    Magewire.hook('commit', function({ succeed }) {
        succeed(function({ effects }) {
            // Process effects
        });
    });
}, { once: true });

// Custom directive
document.addEventListener('magewire:initialized', event => {
    Magewire.directive('mage:my-directive', ({ el, directive, cleanup }) => {
        // Directive logic
        cleanup(() => { /* teardown */ });
    });
});
```

## Always Use `{ once: true }`

Registration event listeners must use `{ once: true }` to prevent double registration on navigations or re-hydrations.

```javascript
// Correct
document.addEventListener('alpine:init', () => { /* ... */ }, { once: true });

// Wrong — may fire multiple times
document.addEventListener('alpine:init', () => { /* ... */ });
```

## Guard Optional Dependencies

Addons, utilities, and Magewire itself may not be available when your code runs. Always check before accessing.

```javascript
document.addEventListener('magewire:init', function() {
    const addons = window.MagewireAddons;

    Magewire.hook('commit', function({ succeed }) {
        succeed(function({ effects }) {
            // Guard addon availability
            if (!addons.has('notifier') || !effects.notifications) {
                return;
            }

            addons.notifier.create(effects.notifications);
        });
    });
}, { once: true });
```

## Escape PHP Values in JavaScript Strings

When injecting PHP values into JavaScript, always use `$escaper->escapeJs()`.

```javascript
const message = '<?= $escaper->escapeJs(__('Item added to cart')) ?>';
const productName = '<?= $escaper->escapeJs($magewire->productName) ?>';
```

Never use `json_encode()` for strings inside JS string literals — it doesn't escape for the HTML/JS context boundary. Use `json_encode()` only for structured data assigned to JS variables.

## Follow the Naming Conventions

| Type | Function name | Registration key |
|------|--------------|-----------------|
| Utility | `magewire{Name}Utility()` | `window.MagewireUtilities.register('name', ...)` |
| Addon | `magewire{Name}Addon()` | `window.MagewireAddons.register('name', ..., reactive)` |
| Alpine component | `magewire{Name}()` | `Alpine.data('magewire{Name}', ...)` |
| Directive | — (IIFE) | `Magewire.directive('mage:{name}', ...)` |

- `'use strict'` goes inside the function body, not at module level
- Utilities return plain objects (no state, no Alpine reactivity)
- Addons return stateful objects, wrapped in `Alpine.reactive()` when the third arg is `true`
- Alpine components are thin wrappers that expose addon state via getters

See the `magewire-javascript` skill for the full pattern reference.

## No Inline Styles

Inline `<style>` blocks and `style="..."` attributes require `style-src 'unsafe-inline'`. Move styles to external CSS files. Alpine's `:style` binding is safe — it uses the DOM API, which isn't controlled by CSP `style-src`.
