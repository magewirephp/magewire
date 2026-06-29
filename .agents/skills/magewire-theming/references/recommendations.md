# Recommendations

Opportunities to improve Magewire's theming story — surfaced while researching this skill. These are not bugs; they are friction points that would make future theme modules easier to author and less error-prone.

Each item names where the friction lives and suggests a concrete change.

## 1. No authoritative list of extension points

**Where**: `src/view/base/layout/default.xml` defines ~12 public containers but the names are scattered across the XML with no class-level documentation block or README.

**Friction**: A theme author has to read the whole layout file and guess which containers are "public" vs "internal". Two containers (`magewire.internal`, `magewire.legacy`) are reserved but nothing in the XML signals that.

**Suggestion**: Add XML comments above each container tagged `<!-- public extension point -->` or `<!-- internal — do not override -->`. Alternatively, expose a PHP constant map (e.g. `Magewirephp\Magewire\Layout\Containers::EXTENSION_POINTS`) that theme code can consume programmatically.

## 2. Feature sort-order tiers are a convention, not a constant

**Where**: CLAUDE.md names three tiers (`1000–2000`, `5000–5200`, `99000+`) but they exist only in prose. `themes/Hyva/etc/frontend/di.xml` picks `99200` empirically.

**Friction**: A new theme module author has no authoritative source for "what number should I use?" A drift where two themes pick the same sort order and override each other is invisible until a bug report.

**Suggestion**: Ship a PHP constants class:

```php
namespace Magewirephp\Magewire\Features;

final class SortOrder
{
    public const LIVEWIRE_BASE        = 1000;
    public const LIVEWIRE_MAX         = 2000;
    public const MAGEWIRE_CORE        = 5000;
    public const MAGEWIRE_CORE_MAX    = 5200;
    public const THEME_EARLY          = 9900;
    public const THEME_LATE           = 99200;
}
```

Reference it in `etc/di.xml` using `<item xsi:type="const">...::THEME_LATE</item>` so sort-order choices are self-documenting.

## 3. `magewire.features` is both a block and a container

**Where**: `src/view/base/layout/default.xml` defines `magewire.features` as a `<block>` with a template, but it's routinely used as a `<referenceContainer>` target.

**Friction**: `<referenceContainer name="magewire.features">` works, but `<referenceBlock name="magewire.features">` also works — and does something different. A junior dev picks the wrong one, replaces the whole feature template, and silently loses every registered feature.

**Suggestion**: Split into two — a `<container name="magewire.features">` (pure layout container) and, if a block template is genuinely needed, a sibling `magewire.features.root` block inside it. Update layout references throughout.

## 4. No generic backwards-compatibility Feature base

**Where**: `themes/Hyva/Magewire/Features/SupportHyvaCheckoutBackwardsCompatibility/SupportHyvaCheckoutBackwardsCompatibility.php` is tightly coupled to `AbstractMagewireAddressForm` — it checks `instanceof AbstractMagewireAddressForm` and merges events from `dispatchBrowserEvent()`.

**Friction**: Any other theme that wants "v1 components run in a v3 world" has to copy this whole class and edit the `instanceof` check. The generic BC plumbing (memo flag, layout-container inheritance) gets duplicated.

**Suggestion**: Extract an abstract `AbstractComponentBackwardsCompatibility extends ComponentHook` in `lib/Magewire/Features/` with:
- a `protected function appliesTo(Component $component): bool` template method (returns `true` by default)
- a `protected function containerHandles(): array` template method (returns layout handles that activate BC, empty by default)
- the existing memo push + hydration logic

Hyvä's class becomes a 15-line subclass overriding the two hooks. New themes get a 15-line subclass for free.

## 5. Undocumented template override path convention

**Where**: Hyvä places overrides at `themes/Hyva/view/frontend/templates/overwrite/Hyva_Theme/page/js/alpinejs.phtml`. The `overwrite/{TargetModule}/{original-path}` pattern is a convention in this codebase but appears nowhere in documentation.

**Friction**: Magento's template fallback is based on `view/{area}/templates/{Module}::path`, not on an `overwrite/` directory. A reader sees `overwrite/Hyva_Theme/...` and assumes it's magical. It isn't — it's cosmetic, and the actual override works because `default_hyva.xml` references it by `Magewirephp_MagewireCompatibilityWithHyva::overwrite/Hyva_Theme/...`.

**Suggestion**: Either document the `overwrite/` convention in the `magewire-theming` skill (done here in `extension-examples.md` §4) or rename to a flatter structure like `templates/hyva/alpinejs.phtml`. Documenting is cheaper and keeps the visual grouping benefit.

## 6. No scaffolding command to generate a new theme module

**Where**: `vendor/bin/portman` has subcommands for building the ported Livewire tree but none for generating a theme module skeleton.

**Friction**: Creating a new theme compat module means copying six files by hand, renaming strings in five of them, and remembering to touch the root `composer.json` PSR-4 map. High friction, low-variance work.

**Suggestion**: Add `portman theme:create --name=MyTheme` that:
- creates `themes/MyTheme/registration.php`, `etc/module.xml`
- adds PSR-4 mapping to root `composer.json`
- prompts for "minimal | with-feature | with-hyva-style-observer" and emits additional scaffolding accordingly
- runs `composer dump-autoload` at the end

Saves every new theme author an hour and eliminates the class of "I forgot the PSR-4 entry" bugs.

## 7. Luma / Breeze / Backend modules are effectively empty

**Where**: `themes/Luma/`, `themes/Breeze/`, `themes/Backend/` all contain only `registration.php` + `etc/module.xml`.

**Friction**: Readers assume these modules must do *something* theme-specific since they exist. They don't — they're marker modules declaring that Magewire is compatible.

**Suggestion**: Add a one-line comment in each `module.xml` (e.g. `<!-- Compatibility marker. Magewire needs no theme-specific integration on Luma. -->`) or a minimal README.md. Alternatively, collapse them into a single `themes/Compat/` module with multiple `<module>` declarations — though that may conflict with Magento's one-module-per-directory expectation.

## 8. Adminhtml theming story is thin

**Where**: Only `themes/Hyva/view/adminhtml/layout/default.xml` exists, and it only wires BC events. No adminhtml-first theme module demonstrates what a backend integration looks like.

**Friction**: A developer building a Magewire component for the admin panel has no template to follow. They don't know which containers render in adminhtml (they all do, because the containers are in `view/base/`), whether `magewire.alpinejs` is relevant (it is, Alpine works in admin), or whether observers on `hyva_config_generate_before`-style events exist (they don't, admin has no equivalent).

**Suggestion**: Either (a) add adminhtml examples to this skill's `extension-examples.md` showing a working admin Magewire component + theme wiring, or (b) promote `themes/Backend/` from a marker module to a live adminhtml reference implementation with one small Feature.

## 9. Tailwind `@source` inheritance is not discoverable

**Where**: `themes/Hyva/view/frontend/tailwind/module.css` uses `@source "../../../../../src/view";`. This is Tailwind v4's way of sharing scanning sets across projects, but the path is fragile (relative to compile location) and undocumented.

**Friction**: A developer copies Hyvä's config to a new theme, Tailwind strips all Magewire classes because the relative path no longer resolves, and the only symptom is a broken notifier style.

**Suggestion**: Either (a) centralize in a `tailwind-preset.js` that themes `require()` — idiomatic Tailwind — or (b) document the relative-path contract explicitly with a comment: `/* keeps Magewire utilities in this theme's Tailwind build; path is relative from compiled CSS output */`.

## 10. `themes/` path is PSR-4-mapped via root composer.json, not via Magento

**Where**: `composer.json` at the package root has entries like `Magewirephp\\MagewireCompatibilityWithHyva\\: themes/Hyva/`. This is correct but non-obvious — a reader opening `themes/Hyva/` and not finding a local `composer.json` expects autoload to be broken.

**Friction**: New theme authors create `themes/NewTheme/composer.json` thinking it's required, then wonder why it causes install issues.

**Suggestion**: Add a `themes/README.md` (one paragraph) explaining: "Theme modules are installed as part of the parent `magewirephp/magewire` package. Their PSR-4 mappings live in the root `composer.json`. Do not create a per-theme `composer.json`."

---

None of these are blockers. The current setup works and Hyvä ships. But each is a paper-cut that theme authors will hit, and fixing any of them makes the second, third, and tenth theme module faster to build than the first one was.

---

## 11. `themes/Backend/` is vestigial; `magewire-admin` is the real thing

**Where**: `themes/Backend/` in the main `magewirephp/magewire` package contains only `registration.php` + `etc/module.xml`. The canonical admin integration lives in the separate `magewirephp/magewire-admin` composer package.

**Friction**: A developer sees `themes/Backend/` and assumes it provides admin integration. It doesn't. They discover `magewire-admin` only after investigating why their admin Magewire component doesn't boot.

**Suggestion**: One of:
- Deprecate `themes/Backend/` with a README pointing to `magewire-admin`
- Fold `magewire-admin`'s Mechanisms, plugin, and resolver into the main package under `src/etc/adminhtml/` so adminhtml works out of the box (standalone package stays for theme-specific admin customization only)
- Expand `themes/Backend/` to require `magewire-admin` transitively so a composer install of the bundled marker pulls in real functionality

The third option preserves the current install story and ends the confusion.

## 12. `magewire-admin` `composer.json` has no version constraint on magewire

**Where**: `vendor/magewirephp/magewire-admin/composer.json` has no `require` section pinning `magewirephp/magewire`.

**Friction**: A project can install an incompatible pair (e.g. magewire-admin built for 3.0 running against magewire 4.x) and get silent runtime breakage — the `Controller` or `Mechanism` base classes may have moved.

**Suggestion**: Add:

```json
"require": {
    "php": ">=8.1",
    "magento/framework": "*",
    "magewirephp/magewire": "^3.0"
}
```

Run CI on both the lowest-supported and latest magewire version to keep the constraint honest.

## 13. `doesPageHaveComponents()` override lacks an exit criterion

**Where**: `magewire-admin/src/Magewire/Mechanisms/ResolveComponents/ResolveComponentsViewModel.php` unconditionally returns `true` to force full boot in admin head-phase rendering.

**Friction**: This is a workaround, not a design. The code has no comment explaining why, no issue link, and no condition under which it should be removed. Future maintainers will either leave it forever or remove it prematurely.

**Suggestion**: Add a docblock citing the cause (head-phase rendering evaluates before body component parsing) and the exit criterion (e.g. "remove when magewire supports a deferred resolver for head-phase scripts, tracked in ISSUE-###"). Ideally also a feature flag so users can opt back to the upstream behavior if admin changes shape.

## 14. Prototype.js fix would fit better as a Magewire Feature

**Where**: `magewire-admin/src/view/adminhtml/web/js/fix-prototype-object-pollution.js` is loaded as a top-level RequireJS dep via `requirejs-config.js`.

**Friction**: The fix is all-or-nothing — can't be disabled via config, not overridable by themes that want a different strategy (e.g. Luma admin with a patched Prototype.js), and not testable in isolation.

**Suggestion**: Wrap it in a Magewire Feature registered via `Magewirephp\Magewire\Features` DI. The Feature renders the shim into `magewire.internal` (or a new `magewire.internal.polyfills` container) only when the target environment is detected (presence of `Prototype` global). That makes it:
- Disableable via the same DI mechanism that toggles other Features
- Testable in isolation
- Skippable on admin pages that don't load Prototype (if any)

## 15. Admin auth uses session cookie comparison, not form key

**Where**: `MagewireUpdateRouteAdminhtml::getMatchConditions()` checks `sessionAuth->getSessionId() === request->getCookie(SESSION_NAME_ADMIN)`.

**Friction**: This is *approximately* CSRF protection — the cookie is set server-side after admin login, so a cross-origin attacker can't forge it. But Magento's convention is explicit form-key validation on admin forms. Security reviewers doing an audit will flag this as "form-key missing" unless they understand the equivalence.

**Suggestion**: Add a docblock on the controller explaining the threat model: session cookie comparison + POST + JSON content type is equivalent to form-key for this endpoint because admin sessions are HTTP-only and same-origin. Alternatively, add form-key validation as a defense-in-depth measure (negligible perf cost, easier to reason about). Either way, make the decision visible in code, not just in the author's head.
