---
name: magewire-theming
description: "Use when building, extending, or debugging a theme-specific Magewire integration for Magento 2 — creating a new theme compatibility module, overriding Magewire layout containers for a theme, registering theme-scoped Features/observers/directives, wiring Alpine loading order for Hyvä/Luma/Breeze/Backend themes, or packaging theme-specific Tailwind CSS. Trigger phrases include: theme module, compatibility module, theme integration, `themes/Hyva`, `themes/Luma`, Hyvä Magewire integration, adminhtml Magewire theming, theme layout override, per-theme Feature, theme-scoped DI, Tailwind in a Magewire theme. Apply whenever the user mentions anything under `themes/` or asks how to make Magewire work with a specific Magento theme. Distinguish from `magewire-javascript` (which is about writing CSP-safe JS for any theme) — this skill is about the **module plumbing** around a theme."
requires: magewire, magewire-architecture, magewire-javascript, magewire-best-practices
license: MIT
metadata:
  author: Willem Poortman
---

# Magewire Theming

How to build and extend **theme compatibility modules** for Magewire — the thin Magento modules that live under `themes/{Theme}/` and wire Magewire into a specific Magento theme (Hyvä, Luma, Breeze, Backend, or a custom one).

## Mental model

Magewire ships in three layers:

1. **Core module** (`src/`) — global controllers, DI, events, layout XML, templates. Theme-agnostic.
2. **Global view layer** (`src/view/base/` + `src/view/frontend/`) — the layout skeleton every theme inherits. Defines named containers for JS addons, utilities, Alpine components, directives, Features.
3. **Theme compatibility modules** — each Magento module in its own right. Declares a dependency on `Magewirephp_Magewire` and plugs into the global view layer via layout references, DI, observers, and Features. These live in two places:
   - **In-tree** at `themes/{Theme}/` inside the main `magewirephp/magewire` package (Hyvä, Luma, Breeze, Backend). Ships with the core package, PSR-4 mapped via the root `composer.json`.
   - **Standalone** as a separate composer package — the canonical example is `magewirephp/magewire-admin`, which provides full adminhtml integration (admin session validation, admin update endpoint, RequireJS compatibility, head-phase script injection).

A theme module's job is to adapt Magewire to one target theme's conventions: Alpine loading order, template overrides, Tailwind pipeline, and any BC shims that theme needs. **Everything reusable belongs in `src/`. Everything theme-specific belongs in a theme module (in-tree or standalone).**

### Why `magewire-admin` is standalone, not in-tree

Adminhtml needs more than layout wiring — it needs a custom controller with admin session validation, a plugin on Magento's `Page\Config\Renderer` to inject scripts before RequireJS, and a RequireJS module that patches Prototype.js pollution. Because those cross-cut controllers, plugins, and a component resolver, they don't fit the minimal "theme folder" shape. `themes/Backend/` in the main package is only a marker — the real integration lives in `magewire-admin`. When building adminhtml Magewire features, treat `magewire-admin` as the canonical reference, not `themes/Backend/`.

## Consistency first

Before creating anything, read the existing theme module that most resembles your target theme:

- Deep integration example: `themes/Hyva/` (Hyvä Checkout needed full v1→v3 BC)
- Minimal compat example: `themes/Luma/`, `themes/Breeze/`, `themes/Backend/` (registration + `module.xml` only)

Copy the established naming, sort order, and container wiring. Inconsistency across theme modules is worse than a suboptimal pattern in one.

## When to use which footprint

| Need | Minimum footprint |
|------|------------------|
| Mark theme as compatible (no real integration) | `registration.php` + `etc/module.xml` |
| Load order tweak (e.g., Alpine before Magewire) | + `view/{area}/layout/default_{theme}.xml` |
| Theme-scoped Feature (lifecycle hook) | + `etc/{area}/di.xml` + `Magewire/Features/*.php` |
| Bridge to theme build pipeline (Tailwind, Hyvä config) | + `Observer/*.php` + `etc/{area}/events.xml` |
| Theme-specific Alpine loaders, directives, BC shims | + `view/{area}/templates/**/*.phtml` |
| Theme-specific CSS | + `view/frontend/tailwind/*` |
| Full adminhtml integration (custom update endpoint, admin session, RequireJS fix, head-phase script injection) | Standalone package like `magewire-admin` with `Controller/*`, `Plugin/*`, `Magewire/Mechanisms/*`, `etc/adminhtml/{di,routes}.xml` |

Add only what the theme needs — empty modules are better than speculative scaffolding.

## Quick reference

### 1. Module structure → `references/module-structure.md`

- Module name convention: `Magewirephp_MagewireCompatibilityWith{Theme}`
- PSR-4 namespace: `Magewirephp\MagewireCompatibilityWith{Theme}\`
- PSR-4 path is added to the root `composer.json` of the `magewirephp/magewire` package, not to a per-theme composer file
- `module.xml` must declare `<sequence><module name="Magewirephp_Magewire"/></sequence>` so Magewire boots first
- If the theme module depends on another Magento module (e.g. `Hyva_Theme`), add that to the sequence too

### 2. Layout containers → `references/layout-containers.md`

The global `default.xml` defines these public extension points. Prefer `<referenceContainer>` (additive) over `<referenceBlock>` (replaces template).

- `magewire.alpinejs.load` — Alpine JS bundle itself; themes reorder script tags here
- `magewire.alpinejs` — global `$wire` / Alpine stores
- `magewire.alpinejs.components` — reusable Alpine data components
- `magewire.utilities` — helper registrations via `MagewireUtilities.register(...)`
- `magewire.addons` — independent plugins via `MagewireAddons.register(...)`
- `magewire.before` — user Alpine directives, UI components, custom wire:* directives
- `magewire.internal.backwards-compatibility` — v1 BC shims only (JS hooks, event aliases)
- `magewire.directives` — custom `wire:*` directives (Magewire-level, not Alpine)
- `magewire.features` — feature-scoped bridge scripts (loaders, rate limiting, flash messages)
- `magewire.after` — last-to-render theme content
- `magewire.legacy`, `magewire.plugin.scripts` — pre-v3 plugin compatibility only

### 3. Extension examples → `references/extension-examples.md`

Copy-paste templates for the common integration patterns:

- Bare theme compatibility module (registration + module.xml)
- Theme-scoped Feature registered via `etc/{area}/di.xml` on `Magewirephp\Magewire\Features`
- Observer on a theme event (e.g. `hyva_config_generate_before`) to bridge into the theme's build pipeline
- Layout override via `default_{theme}.xml` — moving blocks, replacing templates, wrapping originals
- Alpine-loading-order fix (common in Hyvä where Alpine must load before Magewire's own script)
- PHTML override that preserves the original via `<?= $block->getChildHtml() ?>`

### 4. Tailwind pipeline → `references/tailwind.md`

- Core Tailwind config lives at `src/view/frontend/tailwind/tailwind.config.js` and scans `src/view/**/*.phtml|xml`
- Theme modules add their own `themes/{Theme}/view/frontend/tailwind/tailwind.config.js` scanning only their own templates
- Themes use `@source "../../../../../src/view"` in their `module.css` to inherit core classes
- Theme-specific CSS variables override `--notifier-bg`, `--notifier-border`, etc. — no recompile needed if only colors change
- For Hyvä, the `HyvaConfigGenerateBefore` observer registers Magewire's module path into Hyvä's Tailwind extension list so its classes are scanned at build time

### 5. Sort order conventions

When registering a theme-scoped Feature in DI, follow the existing numbering:

- `1000–2000` — ported Livewire Features (do not use for theme code)
- `5000–5200` — Magewire-specific Features (do not use for theme code)
- `9900–9999` — theme Features that must run *before* BC
- `99000+` — theme Features that must run *after* all core BC (Hyvä checkout BC is at `99200`)

Use `sequence` in the DI `item` array to declare hard dependencies (e.g. `magewire_backwards_compatibility` must boot first).

### 6. Area scoping (frontend vs adminhtml)

Theme modules must register DI and layout under the right area:

- `etc/frontend/di.xml` + `view/frontend/layout/` for storefront themes (Hyvä, Luma, Breeze)
- `etc/adminhtml/di.xml` + `view/adminhtml/layout/` for backend themes
- `etc/di.xml` (global) is never used for theme-scoped registration — Features registered globally leak into both areas

Hyvä's `themes/Hyva/view/adminhtml/layout/default.xml` only adds its BC events; if a frontend-only theme doesn't need adminhtml wiring, omit the directory entirely.

### 7. Adminhtml specifics (see `magewire-admin` package)

Adminhtml diverges from frontend in several concrete ways. If you are writing admin Magewire code, read `references/extension-examples.md` sections 8–12 first:

- **Update endpoint**: `/admin/magewire/update` (backend frontname prefix). Admin router must register its own `Controller/MagewireUpdateRouteAdminhtml` that extends the frontend controller and adds admin session validation via `Magento\Backend\Model\Auth\Session\Proxy`.
- **Routes**: `etc/adminhtml/routes.xml` with `router id="admin"` and `front name="magewire"`.
- **Script injection**: Admin templates render in `<head>` before `<body>`. Use a plugin on `Magento\Framework\View\Page\Config\Renderer::afterRenderAssets()` to inject the Magewire `<script>` tag before the first existing `<script>` — otherwise RequireJS loads before Alpine and component boot is racy.
- **Component resolver**: Register a `LayoutAdminResolver` (extends `LayoutResolver`) with accessor `layout_admin` so admin layout XML uses `<magewire>layout_admin</magewire>` rather than `layout`. Resolver sort order `99800` so it runs before the default `99900` resolver.
- **Page detection workaround**: Override `ResolveComponentsViewModel::doesPageHaveComponents()` to return `true` unconditionally — admin head-phase rendering evaluates this before body components exist, so the default heuristic reports no components and Mechanisms/Features never boot.
- **Prototype.js pollution**: Magento admin loads Prototype.js, which pollutes `Object.prototype`. Register a RequireJS module that runs after `'prototype'` and restores `Object.keys` / `Object.values` to only enumerate own keys — otherwise Magewire child-component resolution iterates inherited properties and breaks.
- **Update URI prefix**: Extend the Magewire `Utils\Magewire` view-model and override `getUpdateUri()` to prepend the backend frontname from `BackendConfigOptionsList::CONFIG_PATH_BACKEND_FRONTNAME`.
- **No Tailwind**: Admin uses Magento's own CSS pipeline. Do not ship a Tailwind config for adminhtml theme modules.

### 8. Recommendations discovered during research → `references/recommendations.md`

Opportunities to tighten the theming story in the `magewirephp/magewire` codebase itself — things that bit us during exploration and would help future theme authors:

- Extension points are implicit in layout XML; no authoritative list exists outside this skill
- Feature sort-order tiers are a convention, not a constant — easy to drift
- `magewire.features` doubles as block *and* container, which confuses override intent
- No generic `SupportComponentBackwardsCompatibility` base — Hyvä's implementation is tightly coupled to `AbstractMagewireAddressForm`
- Template override path convention (`overwrite/{Target_Module}/{path}`) is undocumented
- No scaffolding command to generate a new theme module

See `references/recommendations.md` for the full list with file references.

## Anti-patterns

- **Global DI for theme Features.** `etc/di.xml` leaks the Feature into both frontend and adminhtml. Always use `etc/frontend/di.xml` or `etc/adminhtml/di.xml`.
- **Editing `src/view/base/layout/default.xml`** to add theme-specific blocks. That file is theme-agnostic. Add a `default_{theme}.xml` under the theme module and reference the core container.
- **Raw `<script>` tags in theme PHTML.** The core `$magewireFragment->make()->script()` pattern applies everywhere — including theme modules. Breaking CSP on one theme breaks it for that theme's users.
- **Hard-coding FQCNs of theme classes into `src/`.** The core must not reference `Magewirephp\MagewireCompatibilityWith*` classes. All coupling flows the other way — themes depend on core, never vice versa.
- **Using `<referenceBlock>` when you only want to add a sibling.** `<referenceContainer>` appends without destroying the original template. Reserve `<referenceBlock>` for deliberate template replacement.
- **Installing Magento modules for a theme via a separate composer package.** The `themes/{Theme}/` modules ship *inside* `magewirephp/magewire`; they are registered via that package's `composer.json` `autoload.psr-4` map. Do not create a standalone package unless you are forking.

## When to load which reference

- Creating a new theme module from scratch → `module-structure.md` then `extension-examples.md`
- Adding a Feature, observer, or layout override to an existing theme module → `extension-examples.md`
- Debugging where to plug a block in → `layout-containers.md`
- CSS not applying / Tailwind classes missing → `tailwind.md`
- Code review of Magewire theming code → read all reference files before writing review comments
