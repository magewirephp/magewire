# Investigation question

How can discussion #301 be fixed without retaining an implicit requirement for
merchants to rebuild Hyva's Tailwind CSS after installing or updating Magewire?

# VERIFIED

## Core registers itself twice

Core observes `hyva_config_generate_before`. Its observer first appends the
explicit `Magewirephp_Magewire` path and then derives a module name from its own
class namespace. In core that derived name is also `Magewirephp_Magewire`, so
the same path is appended twice.

Evidence:

- `src/etc/frontend/events.xml:5`
- `src/Observer/Frontend/HyvaConfigGenerateBefore.php:35`
- `src/Observer/Frontend/HyvaConfigGenerateBefore.php:40`

## The companion repeats core registration

`magewire-hyva-theme` has a second observer. It appends the core path and then
its own path. With both current packages enabled, the generated registry
contains duplicate Magewire core entries before downstream normalization.

Evidence:

- `magewirephp/magewire-hyva-theme` commit `e9e0796`
- `src/Observer/Frontend/HyvaConfigGenerateBefore.php:35` in the companion
- `src/Observer/Frontend/HyvaConfigGenerateBefore.php:40` in the companion
- [Discussion #301](https://github.com/magewirephp/magewire/discussions/301)

## Core contains a real but very small production Tailwind dependency

Core production templates use Tailwind utility classes for:

- the notifier occurrence badge;
- the notifier item's relative positioning and bounce animation;
- the activity-state size;
- the loading icon's size, spin, opacity, and shadow;
- developer exception spacing, typography, borders, and colors.

This is presentation-only. The directive-state rules are already shipped as a
minified inline `<style>` block and do not use Tailwind at runtime.

Evidence:

- `src/view/base/templates/magewire/ui-components/notifier.phtml:36`
- `src/view/base/templates/js/alpinejs/components/magewire-notifier.phtml:60`
- `src/view/base/templates/magewire/ui-components/notifier/activity-state.phtml:12`
- `src/view/base/templates/magewire/utils/icons/loading.phtml:13`
- `src/view/base/templates/magewire/exception.phtml:26`
- `src/view/base/templates/css/magewire.phtml:1`

## The companion's only Tailwind CSS is generic notifier structure

The companion Tailwind directory contains notifier positioning, spacing,
shadow, occurrence-badge colors, and imports/source declarations. Its runtime
templates contain no Tailwind utility class usage that independently requires a
content scan.

Evidence:

- Companion `src/view/frontend/tailwind/module.css`
- Companion `src/view/frontend/tailwind/tailwind-source.css`
- Companion `src/view/frontend/tailwind/tailwind.config.js`
- Companion `src/view/frontend/tailwind/ui-components/notifier.css`

## Hyva registration exists specifically for build inputs

Hyva's official documentation says `hyva-themes.json` identifies modules whose
templates or CSS/config inputs must participate in Tailwind compilation.
`module.css` is the recommended Tailwind v4 input; legacy
`tailwind.config.js`/`tailwind-source.css` remain fallback inputs.

Evidence:

- [Registering a module for Tailwind compilation](https://docs.hyva.io/hyva-themes/working-with-tailwindcss/registering-a-module-for-tailwind-compilation.html)
- [Compatibility module technical deep dive](https://docs.hyva.io/hyva-themes/compatibility-modules/technical-deep-dive.html)
- [Hyva sources documentation](https://docs.hyva.io/hyva-themes/working-with-tailwindcss/using-hyva-modules/sources.html)

## Both mainstream theme families expose compatible message hooks

The notifier already emits `.message` plus a severity class. Magento Blank and
Hyva both style that contract. Magewire can retain those compatibility hooks
while its own scoped vanilla declarations provide a complete default that does not
depend on either theme's compiled output.

Evidence:

- `src/view/base/templates/js/alpinejs/components/magewire-notifier.phtml:60`
- Magento Blank `web/css/source/_messages.less`
- Hyva default theme `web/tailwind/components/messages.css`

## Core's root npm manifest is currently inert

The root `package.json` declares only `tailwindcss` and `@tailwindcss/cli`. It
has no scripts or lockfile, and no repository workflow invokes it. Playwright
uses `tests/Playwright/package.json` separately.

Evidence:

- `package.json:1`
- `.github/workflows/playwright.yml:284`
- `tests/Playwright/package.json`

## Current browser CI already skips a Hyva Tailwind build

The Playwright workflow explicitly skips the theme build because most tests
assert behavior instead of presentation. This makes it a useful base for a
regression proving Magewire's own computed styles work without compilation.

Evidence:

- `.github/workflows/playwright.yml:3`

## Fylgja informs the authoring approach only

Fylgja demonstrates a modular, buildless approach built around native CSS,
logical properties, and customization through properties. Magewire follows
those authoring principles without copying Fylgja's Toast or Badge components
and without bundling a Fylgja dependency, reset, token set, class, or runtime.
All shipped selectors and custom properties are owned by Magewire.

Evidence:

- [Fylgja buildless documentation](https://fylgja.dev/docs/concepts/buildless/)
- [Fylgja](https://fylgja.dev/)
- `src/view/base/web/css/magewire.css:1`

## Laravel provides the visual direction

Laravel and Laravel Cloud use restrained white/slate surfaces, compact rounded
corners, subtle layered shadows, Laravel red, and Cloud blue. Magewire uses
that visual language as direction for its original defaults without importing
their stylesheets or assets. Core variables keep those choices overridable by
Magento themes.

Evidence:

- [Laravel](https://laravel.com/)
- [Laravel Cloud](https://marketing.cloud.laravel.com/)
- `src/view/base/web/css/magewire.css:48`

## The core implementation works without compiled theme CSS

Magewire now loads `Magewirephp_Magewire::css/magewire.css` as a normal Magento
asset. A focused Playwright regression creates a live notification, disables
every linked theme stylesheet and inline style element, and then verifies the
Magewire asset still provides fixed notifier positioning, flex/relative toast
layout, Laravel-inspired border/radius presentation, and directive-state hiding. The
complete 99-test Playwright suite passes. Magento DI compilation also succeeds
after removing the observer.

Evidence:

- `src/view/base/layout/default.xml:6`
- `src/view/base/web/css/magewire.css:1`
- `tests/Playwright/tests/notifier.spec.js:37`
- `tests/Playwright/tests/multiple-roots.spec.js:17`

## Open PR #291 reinforces the build-time model

PR #291 adds `src/view/frontend/tailwind/module.css` with four `@source`
directives covering core base/frontend layouts and templates. It also retains
the legacy `tailwind.config.js`, changing only its relative paths. The patch is
therefore a Tailwind v4 compatibility fix for the current ownership model, not
a decoupling fix.

PR #290 performs broader Tailwind v3/v4 compatibility work against the legacy
`1.x` branch and is not the implementation vehicle for current Magewire 3.

Evidence:

- [PR #291](https://github.com/magewirephp/magewire/pull/291)
- [PR #290](https://github.com/magewirephp/magewire/pull/290)

# ASSUMED

- The next feature-capable core release can introduce the stylesheet without a
  major-version break, provided existing public class hooks remain available.
- A Magento-managed external stylesheet is preferable to expanding the inline
  `<style>` template because it is cacheable, CSP-friendly, and still requires
  no Node/Tailwind build. Standard Magento static-content deployment remains an
  expected production deployment step.
- Existing custom themes may target current semantic classes such as
  `.magewire-notifier` and `.magewire-notifier-message`; those hooks should be
  retained even when utility classes are removed.

# UNKNOWN

- Magewire 3.7 is the selected compatibility floor, but the release does not
  exist until the core pull request is merged and released.
- Whether any downstream extension intentionally targets the Tailwind utility
  classes currently embedded in Magewire templates. These are generic utility
  tokens rather than documented Magewire API, but release notes should call out
  their removal.
- Whether downstream themes intentionally override `.message.<severity>` in a
  way that conflicts with Magewire's neutral fallback. The buildless regression
  proves isolation; clean Luma/Blank visual QA remains a release gate.

# Conclusion

The duplicate observer is a symptom of a stale ownership model. Because the
production CSS surface is small, the sustainable fix is a pre-baked core asset
made from original, namespaced vanilla CSS, with no frontend toolchain. Core
provides the complete Laravel-inspired default; the companion provides an
optional browser-ready vanilla override. Neither package requires Tailwind.
