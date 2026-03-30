# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is Magewire

Magewire is a reactive component framework for Magento 2, inspired by Laravel Livewire v3. It ports the Livewire PHP core into Magento via a CLI tool called **Portman**, then layers Magento-specific integrations on top.

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

### Code Quality
```bash
vendor/bin/php-cs-fixer fix dist/   # Fix code style in dist/
vendor/bin/rector process dist/     # Apply PHP 8.2 upgrades to dist/
```

## Directory Structure and Rules

| Directory | Description | Edit? |
|-----------|-------------|-------|
| `src/` | Magento module (controllers, blocks, DI, templates, layout XML) | ✅ Yes |
| `lib/Magewire/` | Hand-written Magewire core (Mechanisms, Features, Containers) | ✅ Yes |
| `lib/MagewireBc/` | Backwards compatibility layer (v1→v3 migration) | ✅ Yes |
| `lib/Livewire/` | Downloaded Livewire source cache | ❌ No |
| `dist/` | Portman-generated output (ported + merged Livewire code) | ❌ No |
| `portman/Livewire/` | Augmentation files merged into ported Livewire source | ✅ Yes |
| `themes/` | Theme compatibility modules (Hyvä, Luma, Breeze, Backend) | ✅ Yes |

**PSR-4 autoload:** `src/`, `dist/`, `lib/Magewire/`, and `lib/MagewireBc/` all map to the `Magewirephp\Magewire\` namespace.

## Architecture: Mechanisms vs Features

The runtime is orchestrated by two registries, both booted by `MagewireServiceProvider`:

**Mechanisms** — non-optional core pipeline, booted in priority order:
- `ResolveComponents` (1000) — discovers Magewire blocks from Magento layout
- `PersistentMiddleware` (1050) — carries persistent data across requests
- `HandleComponents` (1100) — snapshot lifecycle, synthesizers
- `HandleRequests` (1200) — orchestrates AJAX update cycle
- `DataStore` (1250) — request-scoped storage
- `FrontendAssets` (1400) — serves the JS bundle

**Features** — optional `ComponentHook` subclasses, hooked into lifecycle signals via `on()`. Named with `SupportMagewire*` (Magewire-specific) or `SupportMagento*` (Magento bridge) prefixes.

**Critical DI rule:** Features and Mechanisms MUST be registered in area-scoped DI (`etc/frontend/di.xml` or `etc/adminhtml/di.xml`), **never** in global `etc/di.xml`. This allows per-area and per-theme customization.

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

To update to a new Livewire version, change `version-lock` in `portman.config.php` and rebuild.

## Skills

Four `.claude/skills/` files provide deep context — use the Skill tool to load them when relevant:

- `magewire` — component API, lifecycle hooks, `wire:*` directives
- `magewire-architecture` — internals: Mechanisms, Features, snapshot flow, DI patterns
- `magewire-javascript` — CSP-compatible JS, Alpine.js integration, multi-theme patterns
- `magewire-portman` — Portman CLI: porting workflow, augmentation files, rebuilding dist/