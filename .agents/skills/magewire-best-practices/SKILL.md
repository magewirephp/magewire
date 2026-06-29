---
name: magewire-best-practices
description: "Use when writing, reviewing, or refactoring Magewire code for Magento 2 — components, PHTML templates, layout XML, DI configuration, Features, Mechanisms, synthesizers, lifecycle hooks, event handling, and JavaScript integration. Trigger phrases include: component design, property handling, security patterns, template rendering, DI registration, snapshot serialization, state management, architectural decisions. Also use for Magewire code reviews and refactoring. Covers any task involving Magewire PHP or JS patterns within Magento 2."
requires: magewire, magewire-architecture, magewire-javascript
license: MIT
metadata:
  author: Willem Poortman
---

# Magewire Best Practices

Best practices for Magewire development in Magento 2, prioritized by impact. Each rule teaches what to do and why. For deeper API reference, load the `magewire`, `magewire-architecture`, or `magewire-javascript` skills.

## Consistency First

Before applying any rule, check what the codebase already does. Magewire bridges two ecosystems — Magento and Livewire — and both offer multiple valid approaches. The best choice is the one the codebase already uses, even if another pattern would be theoretically better. Inconsistency is worse than a suboptimal pattern.

Check sibling components, related Features, existing DI config, and neighboring templates for established patterns. If one exists, follow it — don't introduce a second way. These rules are defaults for when no pattern exists yet, not overrides.

## Quick Reference

### 1. Component Design → `references/components.md`

- Extend `Magewirephp\Magewire\Component`, not Magento block classes
- Keep public properties serializable — scalars, arrays, or types with a registered synthesizer
- Use typed properties with defaults — uninitialized properties break snapshot serialization
- Small, focused components — split large components into parent-child
- Never query the database in the constructor — use `mount()` for initialization
- Use `$this->skipRender()` when a method only changes state without needing a re-render

### 2. Properties & State → `references/properties.md`

- Scalar types and arrays for public properties — complex objects need synthesizers
- `wire:model` for form binding, `wire:model.live` only when instant feedback is required
- `fill()` accepts arrays and DataObjects — use it for bulk property assignment
- `reset()` and `resetExcept()` to restore initial state cleanly
- Dot notation (`address.city`) for nested array/object properties
- Never store sensitive data (passwords, tokens) in public properties — they travel to the client

### 3. Lifecycle Hooks → `references/lifecycle.md`

- `mount()` for initial setup — runs once, receives layout XML arguments
- `boot()` / `booted()` for every-request setup — use for authorization checks
- `hydrate()` / `dehydrate()` for request boundary logic, not business logic
- `updating()` / `updated()` for validation and side effects on property changes
- Property-specific hooks (`updatingName`, `updatedName`) over generic hooks when targeting one property
- Never call `$this->render()` manually — the framework handles the render cycle

### 4. Templates & Views → `references/templates.md`

- Single root element required in every component template
- `$magewire` is the component instance — access properties and methods directly
- `$escaper->escapeHtml()`, `$escaper->escapeUrl()`, `$escaper->escapeJs()` for all dynamic output
- `$block->getChildHtml()` for nested Magento blocks inside a Magewire template
- Never echo raw user input — use Magento's escaper, not PHP's `htmlspecialchars()`
- Keep templates thin — logic belongs in the component class, not in PHTML

### 5. Layout XML & Block Registration → `references/layout.md`

- Component class goes in `<argument name="magewire">` — Magewire can be bound to any block class
- Use `name` attribute for unique block identification — Magento merges by name
- Template path uses module notation: `Vendor_Module::template.phtml`
- Register blocks within the page layout handle where they're needed, not globally

### 6. Dependency Injection → `references/di.md`

- Features and Mechanisms in area-scoped DI only (`etc/frontend/di.xml`, `etc/adminhtml/di.xml`) — never global `etc/di.xml`
- Sort order determines boot sequence — check existing registrations before choosing a number
- Use `boot_mode` to control when a service boots: `10` (lazy), `20` (persistent), `30` (always, default)
- Constructor injection via Magento DI for component dependencies — not `ObjectManager::getInstance()`
- Virtual types for DI-only variations — don't create empty subclasses

### 7. Security → `references/security.md`

- CSRF is automatic — Magewire uses Magento's FormKey, no manual token handling needed
- Snapshot checksums prevent state tampering — never bypass checksum validation
- Escape all output in templates — `$escaper->escapeHtml()` is the default, use specific escapers for URLs, JS, attributes
- Public methods are callable from the frontend — never expose admin actions or destructive operations without authorization
- Public properties are visible to the client — never store secrets, API keys, or session data
- Rate limiting is handled by the `SupportMagewireRateLimiting` feature via configuration, not attributes

### 8. Events & Communication → `references/events.md`

- `$this->dispatch('event-name')` for cross-component communication
- `#[On('event-name')]` attribute for declarative listeners — preferred over `$listeners` array
- `->self()` to scope events to the dispatching component only
- `->up()` to dispatch to the parent component
- Kebab-case event names (`cart-updated`, not `cartUpdated`)
- Don't use events for parent-to-child — pass data via layout XML arguments or properties

### 9. JavaScript Integration → `references/javascript.md`

- CSP-compatible scripts only — use `$magewireFragment->make()->script()->start()/end()`, never raw `<script>` tags
- `magewire:init` for Magewire hooks, `alpine:init` for Alpine registrations, `magewire:initialized` for directives
- Always `{ once: true }` on registration event listeners to prevent double registration
- Access addons via `window.MagewireAddons`, utilities via `window.MagewireUtilities`
- Guard addon access with `addons.has('name')` — addons are optional
- Never depend on `window.Magewire` at registration time — it may not exist yet

### 10. Features & Extensions → `references/features.md`

- Extend `ComponentHook` for lifecycle-based extensions — implement only the hooks you need
- `SupportMagewire*` prefix for Magewire-specific features, `SupportMagento*` for Magento bridge features
- Feature-owned JS/templates live in the feature's folder, not in global `addons/` or `components/`
- Use `storeSet()` / `storeGet()` for component-scoped feature state — not class properties
- Push side effects via `$context->pushEffect()` during `dehydrate()` — never echo output from features
- Facades for public APIs — register with `facade` key in DI config

### 11. Performance → `references/performance.md`

- `$this->skipRender()` when a method call doesn't need to update the DOM
- Avoid `wire:model.live` on high-frequency inputs — use `wire:model.blur` or debounce
- Minimize public properties — every property is serialized on every request
- Lazy-load heavy dependencies — use `boot_mode: 10` for features not always needed
- `wire:loading` for user feedback instead of polling or repeated requests

### 12. Serialization & Synthesizers → `references/synthesizers.md`

- Built-in synthesizers handle: arrays, stdClass, enums, floats, ints, DataObjects
- Custom synthesizers for unsupported types — implement `match()`, `dehydrate()`, `hydrate()`
- Register synthesizers in area-scoped DI with a sort order — first match wins
- `dehydrate()` must return JSON-safe data — no objects, no resources
- Test round-trip serialization: `hydrate(dehydrate($value))` must produce the original value
- Avoid storing Magento models directly — extract needed data into scalar properties

### 13. Conventions & Style → `references/style.md`

- Component classes in `Vendor\Module\Magewire\` namespace — not in `Block\`, `Model\`, or `Controller\`
- One component class per file, named after the component
- `snake_case` for layout XML block names: `checkout.shipping.address`
- `kebab-case` for event names and wire directives: `cart-updated`, `wire:click`
- `PascalCase` for component class names, `camelCase` for methods and properties
- Template files match the component purpose, not the class name: `shipping-address.phtml`, not `ShippingAddress.phtml`
- Feature classes always prefixed with `Support`: `SupportMyFeature`

## How to Apply

1. Identify the file type and select relevant sections (e.g., component → 1, 2, 3; template → 4, 7; DI config → 6; JS → 9)
2. Check sibling files for existing patterns — follow those first per Consistency First
3. For API syntax, load the `magewire` skill for component API or `magewire-architecture` for internals
