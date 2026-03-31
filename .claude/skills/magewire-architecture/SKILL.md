---
name: magewire-architecture
description: >
  Internals and extension guide for the Magewire framework: directory layout
  (src/lib/dist/portman), Mechanisms vs Features, area-scoped DI
  (frontend/adminhtml), snapshot/state flow, layout containers, JS extension
  points, and Facades. Use when extending Magewire, creating custom Features
  or Mechanisms, debugging the framework itself, or understanding how the
  codebase is structured.
license: MIT
metadata:
  author: Willem Poortman
---

# Magewire Architecture

Magewire's codebase is split into two layers: an upstream Livewire core (PHP, ported and maintained via Portman) and a Magento integration layer (controllers, blocks, DI, layout, templates). Understanding this split is essential for knowing where to add or change things.

---

## Repository Layout

```
vendor/magewirephp/magewire/
├── src/                        # Magento module structure (hand-written, must live here for Magento)
│   ├── Component.php           # Base component class (public API)
│   ├── MagewireServiceProvider.php
│   ├── Controller/             # Route controllers (magewire/update)
│   ├── Model/                  # Magento-specific models, view utils, fragment handling
│   ├── Observer/               # Magento event observers
│   ├── Plugin/                 # Magento plugin interceptors
│   ├── ViewModel/              # MagewireViewModel (utilities facade)
│   ├── Exceptions/
│   ├── etc/                    # module.xml, di.xml, events.xml, routes.xml
│   └── view/                   # Layout XML, PHTML templates, JS/CSS assets
│
├── lib/                        # Hand-written custom code + downloaded Livewire source
│   ├── Livewire/               # Downloaded upstream Livewire source (Portman input cache)
│   ├── Magewire/               # Hand-written Magewire core (mechanisms, features, enums, runtime)
│   ├── MagewireBc/             # Hand-written backwards compatibility layer
│   ├── Magento/                # Hand-written Magento framework integrations
│   └── Symfony/                # Hand-written Symfony adaptations
│
├── portman/                    # Portman augmentation files
│   └── Livewire/               # Augmentations: what to change/extend in upstream Livewire
│
├── dist/                       # Portman BUILD OUTPUT — do not edit directly
│   │                           # (Livewire source + augmentations, namespace-transformed)
│   ├── ComponentHook.php
│   ├── Mechanisms/
│   └── Features/
│
├── themes/                     # Theme support sub-modules (e.g. Hyva, Luma)
├── tests/                      # Playwright + other test types
└── portman.config.php          # Controls Portman: source paths, output dir, transformations
```

**Rule of thumb**: `src/` and `lib/` (excluding `lib/Livewire/`) are yours to edit freely. `dist/` is Portman-generated — changes belong in `portman/Livewire/` (augmentations) or `portman.config.php`. `lib/Livewire/` is the downloaded upstream source cache — don't edit it either.

---

## Bootstrap & Runtime

Magewire boots through `MagewireServiceProvider`, which is triggered by Magento's DI during the request lifecycle. It registers three types of services in order:

1. **Containers** — Internal DI-like containers (MagewireManager, etc.)
2. **Mechanisms** — Non-optional core infrastructure (sorted, loaded in order)
3. **Features** — Optional lifecycle hooks (sorted, loaded in order)

Boot modes per service item (`ServiceTypeItemBootMode`):
- `LAZY` (10) — Boot only when needed
- `PERSISTENT` (20) — Boot during setup phase, persists across request modes
- `ALWAYS` (30) — Boot on every request (default)

Runtime state progression: `UNINITIALIZED → SETUP → BOOTING → BOOTED` (or `FAILED / STOPPED`)

Request modes: `PRECEDING` (initial page load) and `SUBSEQUENT` (AJAX update).

---

## Mechanisms

Mechanisms are the non-optional core pipeline. They live in `lib/Magewire/Mechanisms/` (hand-written, Magento-specific) and `dist/Mechanisms/` (ported from Livewire), and run in `sort_order` sequence:

| Sort | Mechanism | Role |
|------|-----------|------|
| 1000 | `ResolveComponents` | Discovers and instantiates components from layout blocks |
| 1050 | `PersistentMiddleware` | Carries persistent data between requests |
| 1100 | `HandleComponents` | Manages snapshot, properties, synthesizers |
| 1200 | `HandleRequests` | Orchestrates the full update cycle |
| 1250 | `DataStore` | Global request-scoped data storage |
| 1400 | `FrontendAssets` | Serves the JS bundle and manifest |

Mechanisms are registered in `src/etc/frontend/di.xml` and `src/etc/adminhtml/di.xml` separately — never in the global `di.xml`. See the note on area-scoped DI below.

To add a custom mechanism, add an entry to the DI config pointing to a class that extends the mechanism base and implements its contract. Mechanisms cannot be skipped — if you only need optional behavior, use a Feature instead.

---

## Features

Features are optional lifecycle extensions. Each Feature is a `ComponentHook` subclass that hooks into the component lifecycle via `provide()`. They live in `lib/Magewire/Features/` (hand-written, Magewire-specific) or `dist/Features/` (ported from Livewire via Portman).

Key built-in features and their sort orders:

| Sort | Feature | Role |
|------|---------|------|
| 700 | `SupportMagewireViewModel` | Auto-injects view_model on blocks |
| 800 | `SupportMagewireExceptionHandling` | Exception management |
| 900 | `SupportMagewireRateLimiting` | Rate limiting |
| 1000 | `SupportNestingComponents` | Parent-child relationships |
| 1200 | `SupportAttributes` | PHP attribute handling |
| 1300 | `SupportRedirects` | Redirect effects |
| 1500 | `SupportLocales` | Locale/i18n support |
| 1600 | `SupportEvents` | Inter-component events |
| 5000 | `SupportMagentoLayouts` | Magento layout rendering |
| 5100 | `SupportMagentoFlashMessages` | Magento session flash messages |
| 5200 | `SupportMagewireLoaders` | Loader state management |
| 5200 | `SupportMagewireNotifications` | Toast notification system |
| 99000 | `SupportLifecycleHooks` | Calls lifecycle methods on components |
| 99100 | `SupportMagewireBackwardsCompatibility` | Legacy API support |

### Naming Conventions

- `SupportMagewire` prefix — Magewire-specific features (e.g. `SupportMagewireLoaders`)
- `SupportMagento` prefix — Magento compatibility features (e.g. `SupportMagentoLayouts`)

### The Hook System

Features hook into component lifecycle events using `on()` / `trigger()`. A hook callback must return either another callback (which receives the result) or the original value unchanged.

```php
use function Magewirephp\Magewire\on;

class SupportMyFeature extends \Magewirephp\Magewire\ComponentHook
{
    public static function provide(): void
    {
        on('magewire:construct', function () {
            // Return a callback — it fires AFTER the trigger resolves
            return function (\Magento\Framework\View\Element\AbstractBlock $block) {
                // Do something with the block
                return $block; // Must return the value
            };
        });
    }
}
```

Hooks are also observable via Magento's native event system through the `SupportMagentoObserverEvents` feature (sort order 99400). It maps 30+ lifecycle hooks to Magento events with the pattern `magewire_on_{eventname}` (special characters replaced with underscores). This means third-party modules can observe Magewire lifecycle events using standard Magento `events.xml` observers without needing to create a Feature.

### Middleware-Style Hooks

Some hooks support a middleware pattern: if your callback returns a callable, that callable runs **after** the operation completes. This is not available on all hooks — only those that support before/after semantics:

- **`update`** — return a callback that runs after the property is updated (used for `updated*` hooks)
- **`call`** — return a callback that runs after method execution
- **`render`** — return a callback that runs after rendering completes (used for `rendered` hook)

```php
on('update', function ($propertyName, $fullPath, $newValue) {
    // Runs BEFORE the property update

    return function () use ($fullPath) {
        // Runs AFTER the property update
    };
});
```

Hooks that do **not** support middleware (e.g., `mount`, `hydrate`, `dehydrate`, `exception`) simply run their callback inline — returning a callable from these has no effect.

### Adding a Custom Feature

1. Create a class extending `Magewirephp\Magewire\ComponentHook`:

```php
namespace Vendor\Module\Magewire\Features;

use Magewirephp\Magewire\ComponentHook;
use function Magewirephp\Magewire\on;

class SupportMyFeature extends ComponentHook
{
    public static function provide(): void
    {
        on('mount', function ($params) {
            // Runs during mount — no middleware support
        });

        on('hydrate', function ($memo) { });

        on('dehydrate', function ($context) { });

        on('update', function ($propertyName, $fullPath, $newValue) {
            // Before update
            return function () {
                // After update (middleware pattern)
            };
        });
    }
}
```

2. Register in `etc/frontend/di.xml` **and** `etc/adminhtml/di.xml` as needed — never in the global `etc/di.xml`. See the note on area-scoped DI below.

```xml
<type name="Magewirephp\Magewire\Features">
    <arguments>
        <argument name="items" xsi:type="array">
            <item name="my_feature" xsi:type="array">
                <item name="type" xsi:type="string">Vendor\Module\Magewire\Features\SupportMyFeature</item>
                <item name="sort_order" xsi:type="number">5050</item>
            </item>
        </argument>
    </arguments>
</type>
```

Place feature PHP code in `src/Features/SupportMyFeature/` and any JS in its corresponding `view/` subfolder.

**Feature template locations differ by context:**
- **Core framework features** (in the Magewire module): JS lives in `view/{area}/templates/js/magewire/features/{feature-name}/`
- **Theme compatibility features** (in theme modules like Hyvä): all feature-related templates (JS, UI, etc.) are bundled in `view/{area}/templates/magewire-features/{feature-name}/` — keeping everything related to a feature in one place for readability

---

## Area-Scoped DI: Why `frontend/di.xml` and `adminhtml/di.xml`

Features and Mechanisms **must** be registered in area-specific DI files (`etc/frontend/di.xml`, `etc/adminhtml/di.xml`) rather than the global `etc/di.xml`.

**Why:** Magento's DI system merges global config into every area. If a Feature or Mechanism were declared in the global `di.xml`, it would be impossible for another module to register a different set for frontend vs adminhtml — the global declaration would apply everywhere and couldn't be selectively overridden per area.

By keeping registrations area-scoped:
- A feature active on the frontend can be absent in the admin (or replaced with a different implementation)
- Third-party compatibility modules (e.g. `MagewireCompatibilityWithHyva`) can add their own features to `frontend/di.xml` without affecting the admin
- The admin panel (`adminhtml/di.xml`) can carry a different, leaner or admin-tailored set of features and mechanisms

**Rule:** When adding a Feature or Mechanism to your own module, always register it in `etc/frontend/di.xml`, `etc/adminhtml/di.xml`, or both — depending on which areas it should be active in. Never use `etc/di.xml` for this.

---

## Component Resolvers & Arguments

The `ResolveComponents` mechanism is Magewire-specific (not ported from Livewire) and is one of the most powerful parts of the framework. It determines **how** a Magento block becomes a Magewire component — and it's designed to be extensible, so that components can come from layout XML, widgets, API calls, or any custom source.

### How Resolution Works

When Magewire encounters a block during rendering, the `ComponentResolverManager` iterates registered resolvers (sorted by DI `sortOrder`) and calls `complies()` on each until one matches. The winning resolver then handles construction (initial page load) and reconstruction (AJAX updates).

Resolvers are registered in area-scoped DI:

```xml
<!-- etc/frontend/di.xml -->
<type name="Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\ComponentResolverManager">
    <arguments>
        <argument name="resolvers" xsi:type="array">
            <item name="layout" xsi:type="object" sortOrder="99900">
                Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\LayoutResolver
            </item>
        </argument>
    </arguments>
</type>
```

The resolver's DI item name must match its `$accessor` property exactly. The accessor is stored in the component's snapshot memo, so subsequent requests can find the right resolver to reconstruct the component.

### The Resolver Contract

Every resolver extends `ComponentResolver` and implements:

| Method | Purpose |
|--------|---------|
| `complies($block, $magewire)` | Lightweight check: does this block belong to this resolver? |
| `construct($block)` | Initial page load: bind a `Component` instance to the block's `magewire` data key, set `name` and `id` |
| `reconstruct($request)` | AJAX update: rebuild the block + component from snapshot data (typically calls `construct()` internally) |
| `arguments()` | Return a `MagewireArguments` subclass for this resolver |
| `assemble($block, $component)` | Final assembly after construct/reconstruct — sets name, id, alias on the component |
| `remember()` | Whether this resolver should be cached (default `true`; set `false` for dynamic resolution) |

```php
namespace Vendor\Module\Mechanisms\ResolveComponents\ComponentResolver;

use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolver;

class WidgetResolver extends ComponentResolver
{
    protected string $accessor = 'widget';

    public function complies(AbstractBlock $block, mixed $magewire = null): bool
    {
        // Check for widget-specific data
        $this->conditions()->if(
            fn () => $block->getData('widget_instance_id') !== null,
            'is-widget'
        );

        return parent::complies($block, $magewire);
    }

    public function construct(AbstractBlock $block): AbstractBlock
    {
        // Instantiate component and bind to block
        $component = Factory::create(MyWidgetComponent::class);
        $block->setData('magewire', $component);
        return $block;
    }

    public function reconstruct(ComponentRequestContext $request): AbstractBlock
    {
        // Rebuild from snapshot — get widget ID from memo, recreate block
        $widgetId = $request->snapshot->memo['widget_id'] ?? null;
        $block = $this->widgetRepository->getBlockById($widgetId);
        return $this->construct($block);
    }

    public function arguments(): MagewireArguments
    {
        return $this->widgetBlockArgumentsFactory->create();
    }
}
```

### ComponentArguments: Structured Block Arguments

Magewire introduces a structured argument system for passing data to components through layout XML. Arguments are extracted from block data during the assembly phase.

**Public arguments** — prefixed with `magewire.`, become component properties:

```xml
<block name="my.component" template="Vendor_Module::my-component.phtml">
    <arguments>
        <argument name="magewire" xsi:type="object">Vendor\Module\Magewire\MyComponent</argument>
        <argument name="magewire.product-id" xsi:type="number">42</argument>
        <argument name="magewire.sort-order" xsi:type="string">price</argument>
    </arguments>
</block>
```

The `magewire.` prefix is stripped and kebab-case is converted to camelCase:
- `magewire.product-id` → `productId`
- `magewire.sort-order` → `sortOrder`

**Group arguments** — prefixed with `magewire:{group}:{key}`, organized into named groups:

```xml
<block name="my.component" template="Vendor_Module::my-component.phtml">
    <arguments>
        <argument name="magewire" xsi:type="object">Vendor\Module\Magewire\MyComponent</argument>
        <argument name="magewire:mount:category-id" xsi:type="number">10</argument>
        <argument name="magewire:mount:page-size" xsi:type="number">20</argument>
        <argument name="magewire:config:cache-ttl" xsi:type="number">3600</argument>
    </arguments>
</block>
```

Group arguments are accessed via the `MagewireArguments` API:

```php
// In your component's mount() — receives the 'mount' group
public function mount(int $categoryId = 0, int $pageSize = 10): void
{
    // $categoryId = 10, $pageSize = 20 (from magewire:mount:*)
}

// In a resolver or feature — access any group
$arguments->forMount();                  // ['categoryId' => 10, 'pageSize' => 20]
$arguments->forGroup('config');          // ['cacheTtl' => 3600]
$arguments->toParams();                  // All public arguments as array
```

**Resolver-specific arguments** — the `magewire:resolver` key can force a specific resolver:

```xml
<argument name="magewire:resolver" xsi:type="string">widget</argument>
```

**Alias** — the `magewire:alias` key sets a component alias for lookup:

```xml
<argument name="magewire:alias" xsi:type="string">shipping-form</argument>
```

### Argument Class Hierarchy

Each resolver provides its own `MagewireArguments` subclass:

```
MagewireArguments (abstract, extends DataObject)
  └── BlockMagewireArguments
        └── LayoutBlockArguments (used by LayoutResolver, marked shared="false" in DI)
```

Custom resolvers create their own subclass for specialized argument handling (e.g., `WidgetBlockArguments` could extract widget configuration parameters).

### The Layout Resolver

The built-in `LayoutResolver` (accessor: `layout`) handles the most common case — components declared in layout XML. It supports three binding formats:

```xml
<!-- 1. Direct object binding (most common) -->
<argument name="magewire" xsi:type="object">Vendor\Module\Magewire\MyComponent</argument>

<!-- 2. Array with type object (allows additional config alongside the component) -->
<argument name="magewire" xsi:type="array">
    <item name="type" xsi:type="object">Vendor\Module\Magewire\MyComponent</item>
</argument>

<!-- 3. Array with type boolean (dynamic component, no physical class) -->
<argument name="magewire" xsi:type="array">
    <item name="type" xsi:type="boolean">true</item>
</argument>
```

On dehydrate, the `LayoutResolver` stores the active layout handles in the snapshot memo. On reconstruction (AJAX), it regenerates the layout from those handles to recover the block.

### Writing a Custom Resolver

To add a new component source (e.g., Magento widgets):

1. Create a resolver class extending `ComponentResolver`
2. Create a `MagewireArguments` subclass for your argument format
3. Register in area-scoped DI with a unique accessor name and sort order

```xml
<type name="Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\ComponentResolverManager">
    <arguments>
        <argument name="resolvers" xsi:type="array">
            <item name="widget" xsi:type="object" sortOrder="50000">
                Vendor\Module\Mechanisms\ResolveComponents\ComponentResolver\WidgetResolver
            </item>
        </argument>
    </arguments>
</type>
```

Lower sort orders are evaluated first. The `LayoutResolver` intentionally uses a high sort order (99900) so it acts as a fallback — custom resolvers should use lower numbers to take priority when their `complies()` check matches.

---

## Snapshot & State Flow

The `Snapshot` is the serialized state of a component, round-tripped between frontend and backend as JSON embedded in the HTML.

```
Snapshot = {
    data: { /* public properties */ },
    memo: { /* metadata: resolver class, children, bindings, etc. */ },
    checksum: /* HMAC of data+memo */
}
```

The checksum prevents tampering. On each AJAX request, Magewire validates it before processing.

`Memo` carries non-property metadata. The `ResolveComponents` mechanism writes the component resolver class name into memo so the component can be reconstructed on subsequent requests without needing layout context.

`Effects` accumulates side effects during the request (redirect, events, method return values) and is sent back to the frontend alongside the new snapshot.

### Synthesizers

Synthesizers (in `dist/Mechanisms/HandleComponents/Synthesizers/` for ported ones, `lib/Magewire/Mechanisms/HandleComponents/Synthesizers/` for Magento-specific ones) handle serialization of non-scalar property types (e.g. `DataObject`, PHP enums, collections). To support a custom type in properties, create a synthesizer and register it in DI.

---

## Request Flow (AJAX Update)

```
POST magewire/update
  → Update controller (src/Controller/Magewire/Update.php)
  → HandleRequestFacade::update()
  → HandleRequests mechanism
      1. Reconstruct component from snapshot memo (resolver class)
      2. boot() + initialize() hooks
      3. hydrate() hooks
      4. booted() hook
      5. Apply property updates → updating/updated hooks
      6. Execute method calls
      7. rendering() hook → render template → rendered() hook
      8. dehydrate() hooks
      9. Generate new snapshot + checksum
     10. Return { snapshot, effects, html }
  ← Alpine.js morphs DOM, stores new snapshot
```

---

## Layout & Template Integration

Magewire's own layout is defined in `src/view/base/layout/default.xml`. It creates a `magewire` root block that outputs all setup scripts (Alpine.js init, utilities, addons, directives, feature scripts).

Magewire can be bound to any block class — not just `Magewirephp\Magewire\Block\Magewire`. The `ResolveComponents` mechanism discovers all blocks with a `magewire` data argument during layout render, uses the appropriate `ComponentResolver` to instantiate the PHP component class, mounts it, and embeds the snapshot as a `wire:snapshot` attribute on the root element.

### Layout Containers

All Magewire layout output is organized in named containers. Extend via standard Magento layout XML:

```xml
<referenceContainer name="magewire.features">
    <block name="my.feature.js" template="Vendor_Module::js/magewire/features/my-feature/my-feature.phtml"/>
</referenceContainer>
```

Full layout reference (from `src/view/base/layout/default.xml`). Elements marked **(block)** have templates; **(container)** are injection points.

| Name | Type | Purpose |
|------|------|---------|
| `magewire` | block | Root block (`root.phtml`) |
| `magewire.global` | block | Global JS setup (`js/magewire/global.phtml`) |
| `magewire.global.before` | container | Before global — holds Alpine load, Alpine code, Alpine components |
| `magewire.alpinejs.load` | container | Alpine JS loading |
| `magewire.alpinejs` | container | Custom Alpine JS code (before Magewire Alpine) |
| `magewire.alpinejs.components` | container | Alpine `Alpine.data()` registrations |
| `magewire.utilities` | block | Utility parent (`js/magewire/utilities.phtml`) — renders child utilities |
| `magewire.utilities.after` | container | Inject custom utilities after core ones |
| `magewire.addons` | block | Addon parent (`js/magewire/addons.phtml`) — renders child addons |
| `magewire.addons.after` | container | Inject custom addons after core ones |
| `magewire.global.after` | container | Custom extensions after the global block |
| `magewire.before` | container | Holds Alpine directives, UI components, Alpine after |
| `magewire.alpinejs.directives` | container | Custom Alpine directives |
| `magewire.ui-components` | container | Alpine UI components (notifier lives here) |
| `magewire.alpinejs.after` | container | Custom Alpine code after Magewire Alpine code |
| `magewire.before.internal` | container | Debug state blocks |
| `magewire.internal` | block | Non-overridable core block — do not inject here |
| `magewire.internal.backwards-compatibility` | container | BC injection point inside internal block |
| `magewire.directives` | block | Directive parent (`js/magewire/directives.phtml`) — renders child directives |
| `magewire.features` | block | Feature parent (`js/magewire/features.phtml`) — renders child feature scripts |
| `magewire.after.internal` | container | Inject content after the internal block |
| `magewire.disabled` | container | Renders only when Magewire is inactive |
| `magewire.after` | container | Everything else (debug tools, HTML blocks) |
| `magewire.legacy` | container | V1 backwards compatibility — do not use for new code |

Note: `magewire.utilities`, `magewire.addons`, `magewire.directives`, and `magewire.features` are **blocks** (not containers). They have parent templates that call `$block->getChildHtml()` to render their children. Add custom children as blocks inside them.

---

## JavaScript Architecture

**All JavaScript must be CSP-compliant.** Never use raw `<script>` tags in templates. All inline scripts must use the fragment utility (`$magewireFragment->make()->script()->start()/end()`), which automatically handles CSP nonce or hash injection depending on the Magento configuration:
- **With Full Page Cache:** generates a SHA256 hash of the script content and adds it to the `script-src` CSP header
- **Without FPC:** adds a `nonce` attribute via Magento's `CspNonceProvider`

The JS bundle (`magewire.csp.min.js`) is Livewire's JavaScript copied directly, without any modifications.
It is served as a Magento static asset via the `FrontendAssets` mechanism, with version pinning via `manifest.json`.
Keeping it untouched is intentional — Livewire bug fixes and feature releases can be adopted simply by replacing the file.

The bundle (all Livewire's own code) handles:
- Alpine.js plugin registration
- `wire:*` directive processing
- Snapshot management and AJAX lifecycle
- DOM morphing
- Effect processing (redirects, events, DOM patches)

**Inline setup scripts** (PHTML templates in `view/base/templates/js/magewire/`) handle:
- `global.phtml` — Defines `MagewireResource`, `window.MagewireAddons`, `window.MagewireUtilities` (must be inline, runs before deferred bundle)
- `i18n.phtml` — Outputs `window.MagewireI18n` translation map
- `features.phtml` — Loads the external deferred feature bundle

These inline scripts run synchronously before the deferred bundle, establishing the global API that the bundle and feature scripts depend on.

---

## Facades (Experimental)

Facades provide a simplified entry point into a Feature or Mechanism's API, abstracting internal complexity.

Register via `di.xml` using the `facade` key:

```xml
<item name="my_feature" xsi:type="array">
    <item name="type" xsi:type="string">Vendor\Module\Features\MyFeature</item>
    <item name="facade" xsi:type="string">Vendor\Module\Features\MyFeature\MyFeatureFacade</item>
</item>
```

Access via the service provider's magic getter:

```php
$facade = $magewireServiceProvider->getMyFeatureFacade();
```

Facades live in their feature/mechanism's own subdirectory. This feature is experimental and may change.

---

## Augmenting Ported Code

`dist/` (configurable via `directories.output` in `portman.config.php`, defaults to `dist/`) is Portman-generated and must not be edited directly. To override or extend a ported Livewire class:

1. Create a matching file in `portman/Livewire/` with the same relative path as the file in `lib/Livewire/`
2. Define a class with the same name using the **source** namespace (`Livewire\`) — Portman merges its methods and properties into the source class at build time
3. Run `vendor/bin/portman build` to regenerate `dist/`

For Magento-level overrides (plugins, preferences), use standard Magento DI on the generated class in `dist/`.

---

## Adding a New Compatibility Module

Magewire ships with compatibility modules for Hyva, Luma, Breeze, and Admin (e.g. `Magewirephp_MagewireCompatibilityWithHyva`). These are separate Magento modules that extend Magewire behavior for a specific frontend stack.

Pattern:
- Declare dependency on `Magewirephp_Magewire` in `module.xml`
- Register additional Features or Mechanisms via DI
- Override or extend layout XML blocks for the target theme
- Add theme-specific JS/PHTML as needed
