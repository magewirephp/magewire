# TL;DR

[GitHub discussion #301](https://github.com/magewirephp/magewire/discussions/301)
correctly identifies duplicate Hyva Tailwind registration, but removing only the
core observer would leave the underlying coupling intact.

The selected fix removes Tailwind from both Magewire packages. Core ships
original vanilla component CSS, written with Fylgja's modular CSS approach as
an inspiration and Laravel/Laravel Cloud as its visual direction. The Hyva
companion ships only an optional browser-ready vanilla override.

The rollout must release core first and make the companion release require that
new core version. Existing companion releases remain usable with the new core
during the transition.

Open PR [#291](https://github.com/magewirephp/magewire/pull/291) should not be
merged as written: it adds the missing Tailwind v4 scan entry point, which
strengthens the build dependency this plan removes.

# Context

- Started: 2026-09-08
- Initial type: Git Discussion
- Current type: Git Discussion
- Status: Core PR open; companion PR drafted behind the Magewire 3.7 release
- GitHub discussion: [#301](https://github.com/magewirephp/magewire/discussions/301)
- Related open pull request: [#291](https://github.com/magewirephp/magewire/pull/291)
- Core pull request: [#305](https://github.com/magewirephp/magewire/pull/305)
- Companion pull request: [magewire-hyva-theme#7](https://github.com/magewirephp/magewire-hyva-theme/pull/7)
- Affected packages: `magewirephp/magewire` and
  `magewirephp/magewire-hyva-theme`

# Goal

Give Magewire's built-in UI a complete Laravel-inspired vanilla CSS baseline,
with no Tailwind build or Fylgja dependency in either package.

# Scope

## Magewire core

- Ship one pre-baked, namespaced stylesheet through Magento's normal frontend
  asset pipeline.
- Use Fylgja's modular structure, logical properties, and custom-property style
  as an authoring reference only; do not copy its component contract or add a
  package/runtime dependency.
- Move the current critical wire-state rules into that stylesheet.
- Replace Tailwind utility classes used by production Magewire templates and
  dynamic bindings with semantic `magewire-*` classes.
- Preserve `.message` and severity classes (`success`, `info`, `warning`, and
  `error`) as compatibility hooks while adding stable `magewire-*` hooks and a
  framework-neutral `data-type` attribute.
- Remove the Hyva event observer, its event registration, and the core
  `view/frontend/tailwind/tailwind.config.js`.
- Remove the root `package.json`; its only dependencies are unused Tailwind
  packages and it defines no build script.

## Hyva theme companion

- Remove its Hyva registry observer and event registration.
- Remove `view/frontend/tailwind/` entirely.
- If a Hyva visual adaptation is desired, ship it as browser-ready vanilla CSS
  through `default_hyva.xml`.
- Remove the README instruction requiring merchants to rebuild the Hyva theme.
- Raise the minimum core requirement to the release containing the pre-baked
  stylesheet.

## Out of scope

- Removing Magewire's public Tailwind-oriented template/compiler utilities.
  They allow consumers to use Tailwind, but do not require Magewire itself to
  be styled or built with Tailwind.
- Bundling CSS authored by third-party Magewire components.
- Copying Fylgja components or applying its reset, tokens, layers, or classes.
- Styling Playwright-only fixture pages as part of the runtime stylesheet.

# Tasks

- [x] Retrieve and verify discussion #301
- [x] Trace core and companion Hyva registration
- [x] Inventory production Tailwind utility usage in core
- [x] Verify the root npm package has no build entry point or consumers
- [x] Inspect existing Tailwind pull requests for overlapping work
- [x] Establish CSS ownership and an upgrade-safe release order
- [x] Decide whether to close PRs #291 and #292 as superseded by #305
- [x] Implement the core pre-baked stylesheet and semantic class migration
- [x] Remove Hyva/Tailwind registration and build metadata from core
- [x] Add computed-style regressions that run without a Tailwind build
- [x] Document Fylgja as an authoring inspiration without bundling it
- [x] Verify Magento DI compilation and the complete live-browser suite
- [ ] Release the core compatibility half
- [x] Implement the companion cleanup in `magewirephp/magewire-hyva-theme`
- [x] Raise the companion's minimum Magewire core version
- [ ] Verify clean-install and mixed-version upgrade scenarios
- [x] Update release notes and the companion installation documentation
- [x] Review both repositories together before release

# Implementation plan

## Phase 1 - Make core self-sufficient

Add `src/view/base/web/css/magewire.css` and load it through the normal Magento
layout `<css>` mechanism. It contains:

- the current `[wire:loading]`, delayed loading, `[wire:offline]`,
  `[wire:dirty]`, progress-color, and `[x-cloak]` rules;
- original Laravel-inspired notifier presentation, positioning, flow,
  occurrence-badge layout, and animations;
- activity-state and loading-icon sizing/animation;
- developer exception presentation and a namespaced visually-hidden helper;
- reduced-motion handling for non-essential animation;
- CSS custom properties for offsets, z-index, gaps, and semantic colors where
  theme customization is likely.

Prefer low-specificity, namespaced selectors and logical properties. The asset
must be valid browser CSS, not Tailwind input containing `@apply`, nesting, or
theme-only tokens.

Update production templates and bindings to use semantic classes. In
particular, remove `relative`, `absolute`, spacing/sizing utilities,
`animate-spin`, `animate-bounce`, opacity, typography, color, border, and shadow
utilities from built-in runtime templates. Keep `.message` and the severity
class on each notification for compatibility, and expose the notification type
through a framework-neutral `data-type` attribute.

Once the external stylesheet covers the current inline rules, remove the
`css/magewire.phtml` block rather than loading the same rules twice.

## Phase 2 - Remove core's Hyva/Tailwind build coupling

Delete:

- `src/Observer/Frontend/HyvaConfigGenerateBefore.php`;
- `src/etc/frontend/events.xml`, because it contains only the Hyva event;
- `src/view/frontend/tailwind/tailwind.config.js`;
- the root `package.json`.

Do not remove `twcss` directive areas or `Model/View/Utils/Tailwind.php`; those
are consumer-facing framework features, not evidence that core assets need a
Tailwind build.

## Phase 3 - Prove core without Tailwind

Browser coverage asserts meaningful computed styles, not merely class names.
The buildless regression disables every theme stylesheet before checking the
core asset. It proves:

- directive-state and cloak selectors are present and effective;
- the notifier is fixed and each notification retains its flex/relative layout;
- the occurrence badge is positioned and content-sized;
- the occurrence emphasis animation remains functional;
- the developer exception preserves readable trace whitespace.

The focused live-browser suite passes with its intentionally skipped Hyva
Tailwind build. A clean-install Luma/Blank page and production-mode static
content deployment remain release gates.

## Phase 4 - Remove Tailwind from the Hyva companion

After the core release is available, update `magewire-hyva-theme` to depend on
that version. Remove its observer, events file, and Tailwind directory. Its
runtime integration stays intact, and an optional vanilla stylesheet loaded by
`default_hyva.xml` restores Hyva's familiar notification presentation without
participating in `hyva-themes.json`.

Update the README so installing or upgrading Magewire does not instruct the
merchant to rebuild Hyva CSS.

## Phase 5 - Verify the rollout

Verify the following combinations before releasing the companion:

| Core | Hyva companion | Expected result |
| --- | --- | --- |
| New | Not installed | Core UI uses pre-baked CSS; no Hyva dependency |
| New | Existing release | Works during transition; old companion may still register/compile redundant sources |
| New | New release | Neither package enters `hyva-themes.json`; no Tailwind rebuild required |
| Old | New release | Composer must reject this combination through the new minimum core constraint |

The decisive install test is: build the Hyva theme first, install the two new
Magewire packages afterward, do not rebuild Tailwind, and confirm the built-in
notifier and exception UI remain structurally correct.

# Decisions

## ✅ Core owns framework-required CSS

Magewire core will ship the CSS required for its own directives and built-in UI
as a ready-to-serve Magento asset.

**Reasoning**

Those rules are framework behavior and structure, not a Hyva adaptation. A
theme-specific build must not be required to make the framework's own loading,
notifier, spinner, and exception surfaces work.

**Evidence**

- `src/view/base/templates/css/magewire.phtml:1`
- `src/view/base/templates/magewire/ui-components/notifier.phtml:36`
- `src/view/base/templates/magewire/ui-components/notifier/activity-state.phtml:12`
- `src/view/base/templates/magewire/utils/icons/loading.phtml:13`
- `src/view/base/templates/magewire/exception.phtml:25`

## ✅ Hyva does not compile Magewire core

Remove core and companion participation in Hyva's Tailwind module registry once
the companion CSS has moved to core.

**Reasoning**

Hyva documents registry participation as the mechanism for modules that ship
Tailwind source/config or require their templates to be scanned. After this
change, neither Magewire package has such inputs.

**Evidence**

- `src/etc/frontend/events.xml:5`
- `src/Observer/Frontend/HyvaConfigGenerateBefore.php:35`
- `src/view/frontend/tailwind/tailwind.config.js:1`
- [Hyva module registration documentation](https://docs.hyva.io/hyva-themes/working-with-tailwindcss/registering-a-module-for-tailwind-compilation.html)
- [Hyva Tailwind asset-merging documentation](https://docs.hyva.io/hyva-themes/compatibility-modules/technical-deep-dive.html)

## ✅ Use Fylgja as an authoring reference, not a framework

Write original vanilla CSS with Magewire-owned selectors and variables. Follow
Fylgja's modularity, logical-property, and customization principles as an
example, without copying its Toast/Badge classes or bundling any Fylgja code.
Use Laravel and Laravel Cloud as the visual direction.

**Reasoning**

This keeps Magewire's CSS contract under its own control and makes the
inspiration clear without turning Fylgja into a dependency. Laravel's neutral
surfaces, restrained shadows, medium radii, and focused red/blue accents give
core a recognizable default that is independent of Magento themes.

**Evidence**

- `src/view/base/web/css/magewire.css:1`
- `src/view/base/templates/js/alpinejs/components/magewire-notifier.phtml:60`
- [Fylgja](https://fylgja.dev/)
- [Laravel](https://laravel.com/)
- [Laravel Cloud](https://marketing.cloud.laravel.com/)

## ✅ Release core before the companion cleanup

The companion release that removes its CSS must require the new core release.

**Reasoning**

New core plus old companion is tolerable: old registration is redundant but
the new core CSS is present. Old core plus new companion would remove notifier
layout CSS before core can replace it, so Composer must prevent that state.

## ✅ Remove the root npm manifest

Delete core's root `package.json` as part of the decoupling.

**Reasoning**

It contains only Tailwind dependencies, has no scripts, has no lockfile, and no
repository code or workflow invokes it. The Playwright project has its own
independent package manifest.

**Evidence**

- `package.json:1`
- `.github/workflows/playwright.yml:284`
- `tests/Playwright/package.json`

## ❌ Rejected: remove only the duplicate core observer

This fixes the duplicate array entry but leaves core templates dependent on a
Hyva/Tailwind scan and leaves merchants responsible for rebuilding theme CSS.
It addresses the symptom described in #301, not the ownership error.

## ❌ Rejected: merge PR #291 as written

PR #291 adds a Tailwind v4 `module.css` that scans core's base and frontend view
files and retains the Tailwind v3 config. It is a correct compatibility patch
only if Magewire intends to remain a Hyva-compiled Tailwind source.

The selected direction removes that premise, so merging #291 would add an
asset entry point that must then be deleted. The PR should be superseded or,
with its author's agreement, repurposed toward the pre-baked CSS migration.

## ❌ Rejected: keep Tailwind and commit its generated output

Precompiling Tailwind utilities into core would hide the consumer build but
retain Tailwind-specific template vocabulary and introduce generated CSS whose
theme tokens and output can drift. The runtime surface is small enough to use
plain semantic CSS directly.

## ❌ Rejected: move all styling to the Hyva companion

The directive-state rules and built-in UI are used by Magewire itself and must
also work on non-Hyva themes. The companion should own integration behavior,
not generic framework presentation.

# Investigation

See [investigation.md](./investigation.md) for verified findings, assumptions,
and remaining release questions.

# Architecture

See [architecture.md](./architecture.md) for the target ownership boundary and
compatibility sequence.

# Tooling

- GitHub discussion metadata and repository history
- GitHub pull requests #290 and #291
- Magewire core source and tests
- Local `magewire-hyva-theme` source at commit `e9e0796`
- Installed Magento Blank/Luma and Hyva theme sources
- Hyva official Tailwind module documentation

# Change log

- 2026-09-08: Created the work item, verified discussion #301, and accepted a
  pre-baked core CSS architecture with a core-first two-package rollout.
- 2026-09-08: Identified PR #291 as an overlapping but superseded Tailwind-build
  approach; no separate remote branch was created.
- 2026-09-08: Initially evaluated Fylgja components as the buildless CSS
  foundation.
- 2026-09-08: Implemented the core half, removed its Hyva/Tailwind registration,
  and passed Magento DI compilation plus all 99 Playwright tests. The buildless
  regression also passed with every theme stylesheet disabled.
- 2026-09-09: Clarified Fylgja as an authoring inspiration only, selected
  Laravel/Laravel Cloud as the visual direction, and removed Tailwind from the
  Hyva companion in favor of a vanilla Magento CSS asset.
- 2026-09-09: Verified the final core CSS has no Hyva-specific naming, passed
  all 99 Playwright tests and Magento DI compilation, and proved the companion
  vanilla stylesheet overrides the core cascade without a frontend build.
- 2026-09-09: Opened core PR #305 and dependent draft companion PR #7.
- 2026-09-09: Audited PRs #291 and #292. Their changed files are removed by
  #305; #292 also omits the existing `wire:offline` rule. Added explicit
  delayed-loading and offline computed-style coverage before closing both as
  superseded.
