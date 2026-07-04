# Port Livewire Lazy Loading → Magewire

## Context

Magewire components currently all mount + render on the initial page load. For heavy components
(cross-sells, blocks below the fold, expensive DB/API work) this hurts Time-To-First-Byte and
page performance. Livewire v3 solves this with **lazy loading**: a component renders a lightweight
placeholder on first paint, then loads its real content in a follow-up XHR — either when it scrolls
into view (`x-intersect`) or immediately after page load (`x-init`, "on-load").

The goal is to bring that feature to Magewire so any component can opt into lazy loading.

### What already exists (do not rebuild)

- **JS side is 100% done.** The shipped bundle (`src/view/base/web/js/magewire.csp.js`, unmodified
  Livewire 3.7.11) already contains `js/features/supportLazyLoading.js` (~line 9960): it reads
  `memo.lazyLoaded` / `memo.lazyIsolated`, and manages commit isolation/bundling. `x-intersect`
  (Alpine intersect plugin) is bundled, and `$wire.__lazyLoad(arg)` works via the generic method
  proxy. **No JS work is needed.**
- **Scaffolding was anticipated.** `MagewireArguments::isLazy()` already exists
  (`lib/Magewire/Mechanisms/ResolveComponents/ComponentArguments/MagewireArguments.php:74`, reads
  `magewire:component:lazy`), and `dist/MagewireManager.php:27` already imports the (currently
  missing) `SupportLazyLoading` class — a dangling import waiting for this port.
- **Component primitives exist.** `skipMount()`, `skipRender($html)`, `skipHydrate()` on
  `src/Component.php:102-115`; `store()`, `trigger()`, `wrap()` helpers in `lib/magewire-helpers.php`.
- The Livewire source is present but **excluded from the Portman build**:
  `portman/lib/Livewire/Features/SupportLazyLoading/{SupportLazyLoading,BaseLazy}.php` and
  `portman/lib/Livewire/Attributes/Lazy.php` — ignored by `portman.config.php`.

### Why not a straight port

Upstream `SupportLazyLoading` is Laravel/Blade-coupled and won't run in Magento:
- `Route::macro('lazy', ...)` — Laravel routing, irrelevant.
- `generatePlaceholderHtml()` builds a `__mountParamsContainer` dynamic component, snapshots it, and
  base64-ferries the mount params. Magewire's `fromSnapshot` is **block-bound**
  (`dist/Mechanisms/HandleComponents/HandleComponents.php:161-167`) so that trick doesn't port. It
  also uses `ViewContext`, which Magewire deliberately does not ship (`portman.config.php:27`).
- `getPlaceholderView()` renders a Blade view (`view()`, `generateBladeView`, `wrap()->placeholder()`).
  Magewire renders through Magento blocks (`toHtml()`), not Blade.

The mechanics we keep (portable as-is): the `mount`/`hydrate`/`dehydrate`/`call` hook shapes,
`store()` flags, and `Utils::insertAttributesIntoHtmlRoot`.

## Design

### Flow (what the port produces)

**Initial page render** — `HandleComponents::mount()` fires `trigger('mount', …)`:
1. Our `SupportLazyLoading::mount($params)` runs first (low sort order, before `SupportLifecycleHooks`
   at 99000). It detects lazy via the `#[Lazy]` attribute (reflection) OR the
   `magewire:component:lazy` layout arg.
2. If lazy: `skipMount()` (LifecycleHooks bails → no real mount), store `isLazyLoadMounting` +
   `isLazyIsolated`, and `skipRender($placeholderHtml)`.
3. `HandleComponents::render()` sees `skipRender` → returns the placeholder HTML with `wire:id`
   injected (`:247-253`); `mount()` adds `wire:snapshot`.
4. `dehydrate` adds `memo.lazyLoaded = false` + `memo.lazyIsolated`. The bundle's JS sees
   `lazyLoaded === false` → marks the component lazy → on intersect/init fires `$wire.__lazyLoad(...)`.

**Lazy XHR** — `$wire.__lazyLoad('<encoded>')`:
1. Component reconstructed from snapshot (LayoutResolver rebuilds block from stored handles).
2. `hydrate($memo)` sees `lazyLoaded === false` → `skipHydrate()` (skips boot/hydrate — it was never
   mounted), store `isLazyLoadHydrating`.
3. `call('__lazyLoad')` intercepts → decode params → run the full mount lifecycle → `returnEarly()`.
4. `render()` now renders the **real** component HTML (skipRender not set this time).
5. `dehydrate` sees `isLazyLoadHydrating` → `memo.lazyLoaded = true` (permanent thereafter).

**Param ferry decision:** ferry mount params as plain `base64(json_encode($params))` embedded in the
`x-intersect`/`x-init` attribute, decoded on `__lazyLoad`. This replaces Livewire's container-snapshot
approach (which can't port — block-bound `fromSnapshot`). Params from layout XML (`magewire:mount:*`)
are scalars, so plain JSON is sufficient and self-contained.

### Files to change

**1. `portman.config.php`** — un-ignore the three source files (edit the `ignore` array):
- `!Attributes/{Locked,On}.php` → `!Attributes/{Locked,On,Lazy}.php`
- add `LazyLoading` to the feature allow-list group:
  `!Features/Support{Attributes,…,Streaming,LazyLoading}/**/*`
  (this ports both `SupportLazyLoading.php` and `BaseLazy.php`).

**2. `portman/Livewire/Features/SupportLazyLoading/SupportLazyLoading.php`** (NEW augmentation) —
namespace `Magewirephp\Magewire\Features\SupportLazyLoading`, `extends
\Livewire\Features\SupportLazyLoading\SupportLazyLoading`. Portman merges these overrides into the
generated `dist/` class (same pattern as `portman/Livewire/Features/SupportRedirects/SupportRedirects.php`).
Override:
- `provide()` — keep the `flush-state` reset; **drop** `registerRouteMacro()`.
- `mount($params)` — Magewire trigger detection: read lazy value from
  `$this->component->magewireResolver()->arguments()->forGroup('component')->get('lazy', false)`
  (`true` or `'on-load'`) **and** the `#[\Magewirephp\Magewire\Attributes\Lazy]` attribute via
  reflection (for `isolate`). Bail if neither present or `$disableWhileTesting`. Otherwise
  `skipMount()`, set store flags, `skipRender($this->generatePlaceholderHtml($params, $lazyMode))`.
- `generatePlaceholderHtml($params, $lazyMode)` — `$encoded = base64_encode(json_encode($params))`;
  `$html = $this->getPlaceholderView($this->component, $params)`; inject via
  `Utils::insertAttributesIntoHtmlRoot($html, [ $lazyMode === 'on-load' ? 'x-init' : 'x-intersect'
  => "\$wire.__lazyLoad('$encoded')" ])`.
- `getPlaceholderView($component, $params)` — the **placeholder resolution** (see below).
- `resurrectMountParams($encoded)` — `json_decode(base64_decode($encoded), true)`.
- `callMountLifecycleMethod($params)` — instantiate Magewire's
  `Features\SupportLifecycleHooks\SupportLifecycleHooks`, `setComponent($this->component)`, `mount($params)`.
- Neutralize `registerContainerComponent()` and `registerRouteMacro()` → empty bodies (removes all
  residual Laravel/Blade/`ViewContext` references from called paths).
- **Keep** upstream `hydrate`, `dehydrate`, `call` (portable — store flags only).

**3. Placeholder resolution** (inside `getPlaceholderView`) — per decision, `placeholder()`
returns either a template id or raw HTML:
- If `method_exists($component, 'placeholder')`: `$result = $component->placeholder($params)`.
- If `$result` matches a template id (regex `^[A-Za-z0-9_]+::.+\.phtml$`, e.g.
  `Vendor_Module::my-placeholder.phtml`): render it as a standalone Magento block —
  `Factory::get(LayoutManager::class)` → layout → `createBlock(\Magento\Framework\View\Element\Template::class)`
  → `setTemplate($result)->addData($params)->toHtml()` (mirrors `FlakeFactory` at
  `lib/Magewire/Features/SupportMagewireFlakes/Component/FlakeFactory.php:58-66`).
- Else treat `$result` as raw HTML.
- Fallback when no `placeholder()` method or empty result: `'<div></div>'`.
- **Requirement (documented):** placeholder markup must have a single root element (so
  `insertAttributesIntoHtmlRoot` can attach the trigger + `wire:id`).

**4. Attributes** — ported automatically by step 1 into `dist/Attributes/Lazy.php` and
`dist/Features/SupportLazyLoading/BaseLazy.php`. `BaseLazy` extends the already-ported
`SupportAttributes\Attribute`; `Lazy` marker carries `public $isolate = true`. No augmentation needed
(clean namespace refs after transformation).

**5. DI registration** — add a Features entry in **both** `src/etc/frontend/di.xml` and
`src/etc/adminhtml/di.xml` (area-scoped, never global):
```xml
<item name="lazy_loading" xsi:type="array">
    <item name="type" xsi:type="string">Magewirephp\Magewire\Features\SupportLazyLoading\SupportLazyLoading</item>
    <item name="sort_order" xsi:type="number">1250</item>
</item>
```
Sort 1250 sits between `attributes` (1200) and `redirects` (1300) — comfortably before
`lifecycle_hooks` (99000), so our `mount`/`hydrate` hooks set their skip flags first.

**6. Rebuild `dist/`** — run `vendor/bin/portman build` to regenerate the ported classes from the
new allow-list + augmentation. (`dist/` is generated — never hand-edited.)

### Usage (result for component authors)

```php
#[\Magewirephp\Magewire\Attributes\Lazy]           // or Lazy(isolate: false)
class HeavyBlock extends \Magewirephp\Magewire\Component
{
    public function placeholder(array $params = []): string
    {
        return 'Vendor_Module::magewire/heavy-block-skeleton.phtml';  // or '<div class="animate-pulse">…</div>'
    }
    public function mount(): void { /* expensive work runs only on the lazy XHR */ }
}
```
or purely via layout XML (no attribute):
```xml
<argument name="magewire:component:lazy" xsi:type="string">on-load</argument>  <!-- or "true" -->
```

## Verification

1. `vendor/bin/portman build` succeeds; confirm generated files exist:
   `dist/Features/SupportLazyLoading/SupportLazyLoading.php`, `dist/Features/SupportLazyLoading/BaseLazy.php`,
   `dist/Attributes/Lazy.php`. Confirm the merged `SupportLazyLoading` has the Magento overrides and
   no `Route::`/`ViewContext`/`view(` refs in reachable methods.
2. `php82 bin/magento setup:di:compile` is NOT required (developer mode) — just confirm no DI errors
   loading a page (clear generated/cache if needed).
3. **End-to-end in a real theme** (Hyvä Checkout is available): mark an existing Magewire component
   lazy (attribute or layout arg), give it a `placeholder()`. Load the page:
   - Initial HTML shows the placeholder markup with `wire:snapshot` whose `memo.lazyLoaded === false`
     and an `x-intersect` (or `x-init` for on-load) calling `$wire.__lazyLoad('…')`.
   - On scroll-into-view (or immediately for on-load), a `magewire/update` XHR fires; response HTML is
     the real component; DOM morphs; new snapshot has `memo.lazyLoaded === true`.
   - `mount()` side effects (queries/logs) occur only on the XHR, not initial paint.
4. Test `isolate: false` (bundled) vs default isolation by placing two lazy components on one page and
   watching whether their lazy-load commits bundle into one request (JS `commit.pooling` behavior).
5. Regression: a non-lazy component still mounts+renders normally on first paint.

## Risks / notes

- **Portman augmentation merge**: adding a constructor to the augmentation is avoided on purpose —
  `LayoutManager` is resolved lazily via `Factory::get()` inside placeholder rendering, sidestepping
  constructor-merge edge cases. Verify method-override merge produces the expected `dist/` output.
- **Single-root placeholder** is mandatory (same constraint as Livewire); document it and default to
  `<div></div>`.
- **Param fidelity**: plain-JSON ferry supports scalar/array layout params. Non-serializable object
  params passed at mount are out of scope for this first port (layout XML args are scalars anyway).
- `dist/MagewireManager.php:27`'s dangling `SupportLazyLoading` import resolves once the class is
  generated — no separate fix needed.