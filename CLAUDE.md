# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is Magewire

Magewire is a reactive component framework for Magento 2, inspired by Laravel Livewire v3. It ports the Livewire PHP core into Magento via a CLI tool called **Portman**, then layers Magento-specific integrations on top. Currently at **3.0.0-beta1**.

## Commands

### Styles (Tailwind CSS)
```bash
npm install
npx @tailwindcss/cli -i ./styles/magewire.css -o ./src/view/base/web/css/magewire.css --optimize
# Add --watch to re-compile on changes
```

### Portman (Livewire → Magewire port)
```bash
vendor/bin/portman build    # Re-generate dist/ from portman/ augmentations + Livewire source
vendor/bin/portman watch    # Watch for augmentation changes and rebuild
```

### Code Quality (targets `dist/` only — run after `portman build`)
```bash
vendor/bin/php-cs-fixer fix dist/   # Fix code style in dist/
vendor/bin/rector process dist/     # Apply PHP 8.2 upgrades to dist/
```

### Static Analysis (Mago — targets `lib/`, `src/`, `themes/`)
```bash
mago lint                           # Run linter on source directories
mago format                         # Format code (PSR-12 preset, see mago.toml)
```
Note: `dist/`, `node_modules/`, and `lib/Livewire` are excluded. Dead code and unused definition analysis are disabled due to Magento DI patterns. Formatter uses PSR-12 with single quotes, 200 char print width, and `sort-uses` enabled (see `mago.toml`).

### Playwright E2E Tests
```bash
cd tests/Playwright
cp .env.example .env               # Configure BASE_URL + credentials
npm install && npx playwright install
npx playwright test                 # Run headless
npx playwright test --ui            # Interactive UI mode
npx playwright test tests/example.spec.ts  # Run a single test file
```
Requires Magento Sample Data. Test fixtures provide browser contexts for guest, customer, admin, and API access.

## Git Hooks (CaptainHook)

- **Pre-commit:** automatically runs `vendor/bin/portman build` — ensures `dist/` stays in sync
- **Commit-msg:** enforces [Conventional Commits](https://www.conventionalcommits.org/) format

## Directory Structure and Rules

| Directory | Description | Edit? |
|-----------|-------------|-------|
| `src/` | Magento module (controllers, blocks, DI, templates, layout XML) | ✅ Yes |
| `lib/Magewire/` | Hand-written Magewire core (Mechanisms, Features, Containers) | ✅ Yes |
| `lib/MagewireBc/` | Backwards compatibility layer (v1→v3 migration) | ✅ Yes |
| `lib/Magento/` | Magento framework extensions (`Magewirephp\Magento\` namespace) | ✅ Yes |
| `lib/Symfony/` | Symfony utility imports (`Magewirephp\Symfony\` namespace) | ✅ Yes |
| `lib/Livewire/` | Downloaded Livewire source cache | ❌ No |
| `dist/` | Portman-generated output (ported + merged Livewire code) | ❌ No |
| `portman/Livewire/` | Augmentation files merged into ported Livewire source | ✅ Yes |
| `themes/` | Theme compatibility modules (Hyvä, Luma, Breeze, Backend) | ✅ Yes |

**PSR-4 autoload:** `src/`, `dist/`, `lib/Magewire/`, and `lib/MagewireBc/` all map to the `Magewirephp\Magewire\` namespace. Each theme module has its own namespace (e.g., `Magewirephp\MagewireCompatibilityWithHyva\`).

## Global Helper Functions (`lib/magewire-helpers.php`)

Autoloaded via `composer.json` `files` — available globally:
- `str($string)` — wraps `Illuminate\Support\Str`
- `invade($obj)` — private property/method access via reflection
- `once($fn)` — ensures callback runs only once
- `app()` — Magento ObjectManager access
- `store($component)` — component-scoped data store
- `trigger($event, ...)` — dispatch to component hooks
- `config($path)` — Magento config value access
- `hook()` — hook/event registration helper

## CI Workflows (`.github/workflows/`)

- **`portman.yml`** — on PRs to `main` touching `portman/`, `src/`, or `lib/`: runs `portman build` and auto-commits any `dist/` changes
- **`release-please.yml`** — on push to `main`: semantic versioning via conventional commits

## Architecture: Mechanisms vs Features

The runtime is orchestrated by two registries, both booted by `MagewireServiceProvider` in phases:
1. **`setup()`** — boots Containers and persistent/setup-level services
2. **`boot(RequestMode)`** — boots remaining Mechanisms and Features for the current request mode (`PRECEDING` for initial page load, `SUBSEQUENT` for AJAX)

State progression: `UNINITIALIZED → SETUP → BOOTING → BOOTED` (or `FAILED`)

**Mechanisms** — non-optional core pipeline, booted in priority order:
- `ResolveComponents` (1000) — discovers Magewire blocks from Magento layout
- `PersistentMiddleware` (1050) — carries persistent data across requests
- `HandleComponents` (1100) — snapshot lifecycle, synthesizers
- `HandleRequests` (1200) — orchestrates AJAX update cycle
- `DataStore` (1250) — request-scoped storage
- `FrontendAssets` (1400) — serves the JS bundle

**Features** — optional `ComponentHook` subclasses, hooked into lifecycle signals via `on()`. Named with `SupportMagewire*` (Magewire-specific) or `SupportMagento*` (Magento bridge) prefixes.

**Critical DI rule:** Features and Mechanisms MUST be registered in area-scoped DI (`etc/frontend/di.xml` or `etc/adminhtml/di.xml`), **never** in global `etc/di.xml`. This allows per-area and per-theme customization.

### DI Registration Item Structure

Each Mechanism/Feature is registered as a named item with these optional keys:

| Key | Type | Purpose |
|-----|------|---------|
| `type` | string | Fully-qualified class name (required) |
| `sort_order` | number | Boot priority — lower loads first |
| `facade` | string | Request-scoped facade class for external access |
| `view_model` | object | ViewModel for PHTML rendering context |
| `boot_mode` | number | When to boot: `10` LAZY, `20` PERSISTENT (survives across requests), `30` ALWAYS (default) |
| `data` | array | Mechanism/Feature-specific configuration (e.g., script paths, query params) |
| `sequence` | array | Dependencies on other Features that must boot first |

### Feature Sort Order Convention

- **1000–2000:** Ported Livewire features (follow upstream registration order)
- **5000–5200:** Magewire-specific features (Magento bridges, loaders, notifications)
- **99000+:** Highest priority — must load last (lifecycle hooks, compiler, observer events)

### Containers (Laravel `app()` Bridge)

Livewire code calls `app()` to resolve services from Laravel's container. In Magewire, the `Containers` service type maps these lookups to Magento DI bindings registered in area-scoped `di.xml`. Currently provides `livewire` and `redirect` containers.

### Synthesizers

Registered via DI on `HandleComponents`, synthesizers handle serialization of complex property types into snapshots. Built-in synths: `DataObjectSynth` (Magento DataObjects), `ArraySynth`, `EnumSynth`, `FloatSynth`, `IntSynth`, `StdClassSynth`. Custom synthesizers can be added via DI.

### Template Compiler & Directives

The `SupportMagewireCompiling` feature provides a PHTML template compiler with directive areas, each scoped to a domain:

- **Base** (no prefix): `@json`, `@if`, `@foreach`, `@script`, `@fragment`, `@slot`, `@template`, `@translate`, `@child`, `@auth`, `@guest`
- **Block** (`@render.*`): `@render.parent`, `@render.child`
- **Escape** (`@escape.*`): `@escape.url`, `@escape.attr`, `@escape.js`, `@escape.html`, `@escape.css`
- **Magewire** (`@magewire.*`), **AlpineJS** (`@alpinejs.*`), **TailwindCSS** (`@twcss.*`): extensible via DI

Directives and directive areas are registered in area-scoped DI as virtualTypes, making them customizable per-area/per-theme.

### Component Resolvers

The `ResolveComponents` mechanism uses pluggable resolvers (registered via DI on `ComponentResolverManager`) to discover and reconstruct components. The default `LayoutResolver` finds Magewire blocks from Magento layout XML.

## Magento Entry Points (`src/etc/events.xml`)

Three global observers bootstrap the framework into Magento's request lifecycle:
- `controller_action_predispatch` — early setup via `ControllerActionPredispatch`
- `view_block_abstract_to_html_before` — intercepts block rendering to resolve Magewire components
- `view_block_abstract_to_html_after` — completes component rendering after block output

The update endpoint is registered via `src/etc/frontend/di.xml` as a custom router (sort_order 5) handling `magewire/update` POST requests.

## Snapshot / State Flow

Each component's state is serialized into a **Snapshot** (data + memo + checksum) and passed between frontend and backend on every request. The flow:

1. **Initial render (`PRECEDING` mode):** `ResolveComponents` finds blocks → `HandleComponents` boots each component, runs lifecycle hooks, renders PHTML → snapshot embedded in HTML
2. **AJAX update (`SUBSEQUENT` mode):** Frontend POSTs to `magewire/update` → `HandleRequests` dequeues updates (calls, sets) → re-renders component → returns new snapshot + effects

## JavaScript / Frontend

- The Livewire JS bundle lives at `src/view/base/web/js/magewire.*.js` (do not edit)
- All Magewire-authored JS is in PHTML templates under `src/view/base/templates/js/`
- **CSP-compatible pattern:** use `$magewireFragment->make()->script()->start()/end()` — no raw `<script>` tags
- Alpine.js integration is in `src/view/base/templates/js/alpinejs/`
- `window.MagewireResource`, `window.MagewireAddons`, and `window.MagewireUtilities` are defined in `src/view/base/templates/js/magewire/global.phtml`

## Portman Augmentation Workflow

To modify ported Livewire code:
1. Edit or add files in `portman/Livewire/` (mirroring the Livewire source structure)
2. Run `vendor/bin/portman build`
3. The result lands in `dist/` with namespace `Livewire\` → `Magewirephp\Magewire\`

To update to a new Livewire version, change `version-lock` in `portman.config.php` (currently pinned to `~3.7.11`) and rebuild.

## Skills

Skill files provide deep context — use the Skill tool to load them when relevant. Core skills live under `.claude/skills/`; theme-specific skills live under `themes/{Theme}/.claude/skills/`.

Core (theme-agnostic):

- `magewire` — component API, lifecycle hooks, `wire:*` directives
- `magewire-architecture` — internals: Mechanisms, Features, snapshot flow, DI patterns
- `magewire-javascript` — CSP-compatible JS, Alpine.js integration, multi-theme patterns
- `magewire-portman` — Portman CLI: porting workflow, augmentation files, rebuilding dist/
- `magewire-best-practices` — coding rules for components, properties, templates, DI, events, security, performance
- `magewire-theming` — building theme compatibility modules under `themes/`
- `magewire-backwards-compatibility` — v1→v3 framework-level BC, BC memo flag, wire:model/entangle behavioral changes

Theme-specific:

- `magewire-hyva-checkout-backwards-compatibility` (`themes/Hyva/.claude/skills/`) — Hyvä Checkout JS BC layer, `hyva-checkout-main` auto-enable rule, `SupportHyvaCheckoutBackwardsCompatibility` Feature