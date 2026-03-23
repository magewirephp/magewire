---
name: magewire-architecture
description: >
  Internals and extension guide for the Magewire framework: directory layout
  (src/lib/dist/portman), Mechanisms vs Features, area-scoped DI
  (frontend/adminhtml), snapshot/state flow, layout containers, JS extension
  points, and Facades. Use when extending Magewire, creating custom Features
  or Mechanisms, debugging the framework itself, or understanding how the
  codebase is structured.
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
- `EAGER` — Boot immediately
- `LAZY` — Boot only when first needed
- `DEFERRED` — Boot at end of request

Runtime state progression: `UNINITIALIZED → SETUP → DEGRADED → READY → FAILED / STOPPED`

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
| 100 (lazy) | `SupportMagewireViewModel` | Auto-injects view_model on blocks |
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

Hooks are not equivalent to Magento Observer Events — they are internal lifecycle signals. Standard Magento observer integration is on the roadmap.

### Adding a Custom Feature

1. Create a class extending `Magewirephp\Magewire\ComponentHook`:

```php
namespace Vendor\Module\Magewire\Features;

use Magewirephp\Magewire\ComponentHook;

class SupportMyFeature extends ComponentHook
{
    public static function provide(): void
    {
        static::on('mount', function ($params) {
            // $this->component is the component instance
        });

        static::on('hydrate', function ($memo) { });

        static::on('dehydrate', function ($context) { });
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

Component blocks use `Magewirephp\Magewire\Block\Magewire` as the block class. The `ResolveComponents` mechanism discovers all such blocks during layout render, instantiates the PHP component class (via `ComponentResolver`), mounts it, and embeds the snapshot as a `wire:snapshot` attribute on the root element.

### Layout Containers

All Magewire layout output is organized in named containers. Extend via standard Magento layout XML:

```xml
<referenceContainer name="magewire.features">
    <block name="my.feature.js" template="Vendor_Module::js/magewire/features/my-feature/my-feature.phtml"/>
</referenceContainer>
```

Full container reference:

| Container | Purpose |
|-----------|---------|
| `magewire.global.before` | Holds `magewire.alpinejs.load`, `magewire.alpinejs`, `magewire.alpinejs.components` |
| `magewire.alpinejs` | Global Alpine code (stores, plugins) — runs before Magewire Alpine code |
| `magewire.alpinejs.components` | Alpine component registrations (`Alpine.data`) |
| `magewire.global.after` | Custom extensions after the global block |
| `magewire.utilities` | Utility helper registrations |
| `magewire.addons` | Addon registrations |
| `magewire.before` | Holds `magewire.alpinejs.directives`, `magewire.ui-components`, `magewire.alpinejs.after` |
| `magewire.alpinejs.directives` | Custom Alpine directives (inside `magewire.before`) |
| `magewire.ui-components` | Alpine UI components like the notifier (inside `magewire.before`) |
| `magewire.alpinejs.after` | Custom Alpine code after Magewire's Alpine code (inside `magewire.before`) |
| `magewire.before.internal` | Internal state blocks (e.g. enabled/disabled indicators) |
| `magewire.internal` | Non-overridable core block — do not inject here |
| `magewire.directives` | Magewire directive scripts (`mage:*`) |
| `magewire.features` | Feature JS hooks |
| `magewire.after.internal` | Inject content after the internal block |
| `magewire.disabled` | Renders only when Magewire is inactive |
| `magewire.after` | Everything else (debug tools, HTML blocks) |
| `magewire.legacy` | V1 backwards compatibility — do not use for new code |

---

## JavaScript Architecture

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

`dist/` is Portman-generated and must not be edited directly. To override or extend a ported Livewire class:

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
