# Layout Containers

The global `src/view/base/layout/default.xml` defines a tree of named containers and blocks that themes plug into. Knowing which one to target is the difference between an override that works and one that silently no-ops.

## Container tree (simplified)

```
magewire (root block — src/view/base/templates/root.phtml)
├── magewire.global
│   ├── magewire.global.before
│   │   ├── magewire.alpinejs.load           ← Alpine JS <script> tags
│   │   ├── magewire.alpinejs                ← Alpine stores, $wire
│   │   └── magewire.alpinejs.components     ← Alpine.data(...) registrations
│   ├── magewire.utilities                   ← MagewireUtilities.register(...)
│   └── magewire.addons                      ← MagewireAddons.register(...)
├── magewire.before                          ← user Alpine directives / UI components
├── magewire.internal
│   └── magewire.internal.backwards-compatibility   ← v1 BC shims only
├── magewire.directives                      ← Magewire wire:* directives
├── magewire.features                        ← Feature-scoped bridge scripts
├── magewire.after                           ← last-to-render theme content
└── magewire.legacy
    └── magewire.plugin.scripts              ← pre-v3 plugin compat only
```

Frontend layout (`src/view/frontend/layout/default.xml`) adds:

- `magewire.alpinejs.components.magewire-script` inside `magewire.alpinejs.components`
- `magewire.object-proxy` inside `after.body.start`
- A `<move>` of the whole `magewire` block to `before.body.end`

## Which to target

| If you are… | Target | Why |
|-------------|--------|-----|
| Reordering Alpine bundle loading | `magewire.alpinejs.load` | Script tag order is controlled here. Hyvä moves the native Alpine script in. |
| Registering a global JS helper | `magewire.utilities` or `magewire.addons` | Utilities = helpers consumed by other Magewire code. Addons = independent plugins. |
| Adding an Alpine data component | `magewire.alpinejs.components` | Runs inside `alpine:init`. Your `Alpine.data('foo', ...)` lands here. |
| Adding a reusable Alpine directive | `magewire.before` | User-scoped directives. Applied before core directives run. |
| Adding a `wire:*` directive | `magewire.directives` | Magewire-level directive registration (e.g. `wire:select`). |
| Bridging a Magewire Feature to its JS side | `magewire.features` | One child block per Feature, by convention. |
| Shim v1 behavior (Livewire v2 quirks) | `magewire.internal.backwards-compatibility` | Internal container; not for general use. Reserved for BC code. |
| Adding a plugin script for v1 compatibility | `magewire.plugin.scripts` | Pre-v3 plugins expect this container. |
| Injecting theme-final content | `magewire.after` | Runs last, after everything Magewire-owned. |

## `<referenceContainer>` vs `<referenceBlock>`

```xml
<!-- Adds a sibling block. Original content keeps rendering. -->
<referenceContainer name="magewire.features">
  <block name="magewire.features.my-bridge"
         template="MyVendor_MyModule::magewire-features/my-bridge.phtml"/>
</referenceContainer>

<!-- Replaces the template. Original content is gone. -->
<referenceBlock name="magewire.features.magewire-notifier"
                template="MyVendor_MyModule::override-notifier.phtml"/>
```

Default to `<referenceContainer>`. Reach for `<referenceBlock>` only when you deliberately want to swap the template (e.g. overriding an upstream Alpine loader to wrap it).

## Moving elements

```xml
<move element="script-alpine-js" destination="magewire.alpinejs.load" before="-"/>
```

`<move>` preserves the original block instance and re-parents it. Useful when an existing theme already renders a block somewhere else and you need it in Magewire's loading order.

- `before="-"` = first child
- `after="-"` = last child
- `before="blockName"` / `after="blockName"` = relative to a sibling

## Page-specific vs global overrides

- `default_{theme}.xml` — applies on every page where the theme is active. Use for load-order fixes, global Feature bridges, BC shims.
- `{route}_{controller}_{action}.xml` — applies on one route only. Use for page-scoped Features (e.g. Hyvä Checkout's BC feature is activated only on `hyva_checkout_index_index`).

Smaller scope is cheaper — page-specific handles avoid paying for the block on every page.

## Containers that are off-limits for themes

- `magewire.internal` and anything under it (except the BC sub-container) — reserved for core machinery.
- `magewire` root block itself — do not replace its template. Override children instead.

If a container you need doesn't exist, add one in `default_{theme}.xml` as a child of an existing container rather than repurposing a reserved one.

## Sort order within a container

Magento layout XML honors `after=` / `before=` among siblings. For deterministic ordering within a container:

```xml
<block name="magewire.features.my-bridge"
       template="..."
       after="magewire.features.support-magewire-loaders"/>
```

Do not rely on file load order — that depends on module sequence and is brittle.

## Adminhtml layout differences

The base container tree defined in `src/view/base/layout/default.xml` applies to both frontend and adminhtml (base layouts merge into both areas). But admin adds two important deviations:

### `magewire.head` — admin-only head container

In admin, scripts must load in `<head>` before RequireJS. `magewire-admin` creates a `magewire.head` block holding the Magewire script tag and a sibling `magewire.script` child, then a plugin on `Page\Config\Renderer::afterRenderAssets()` injects the rendered block's HTML ahead of the first `<script>` tag in the output.

```xml
<!-- magewire-admin/src/view/adminhtml/layout/default.xml -->
<move element="magewire" destination="root"/>

<referenceContainer name="root">
  <block name="magewire.head"
         template="Magewirephp_MagewireAdmin::js/magewire/head.phtml">
    <block name="magewire.script"
           template="Magewirephp_MagewireAdmin::js/magewire/head/script.phtml"/>
  </block>
</referenceContainer>
```

The plugin then does:

```php
preg_replace('/(<script\b[^>]*>)/i', $head->toHtml() . '$1', $result, 1);
```

Frontend doesn't need this — layout XML's native ordering (`before.body.end`, `after.body.start`) is enough because the frontend has no RequireJS race.

### `layout_admin` component accessor

In frontend layout XML, components are bound with `<magewire>layout</magewire>`. In admin, the accessor is `layout_admin`:

```xml
<!-- admin layout XML -->
<block name="my.admin.component"
       class="Vendor\Module\Magewire\MyAdminComponent"
       template="Vendor_Module::admin-component.phtml">
    <arguments>
        <argument name="magewire" xsi:type="string">layout_admin</argument>
    </arguments>
</block>
```

This works because `magewire-admin` registers a `LayoutAdminResolver` (sort order `99800`, before the default `99900` frontend resolver):

```xml
<!-- magewire-admin/src/etc/adminhtml/di.xml -->
<type name="Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolverManagement">
    <argument name="resolvers" xsi:type="array">
        <item name="layout_admin" xsi:type="object" sortOrder="99800">
            Magewirephp\MagewireAdmin\Magewire\Mechanisms\ResolveComponents\ComponentResolver\LayoutAdminResolver
        </item>
    </argument>
</type>
```

If you're writing admin components without `magewire-admin` installed, they won't resolve. Require the package.

### `doesPageHaveComponents()` always true in admin

The base ViewModel decides whether to boot all Mechanisms/Features based on whether any components are present on the page. In admin, scripts render in the head before the body has been parsed — so the naive check reports zero components and Magewire skips the full boot. `magewire-admin` overrides the view model to always report `true`:

```php
// magewire-admin/src/Magewire/Mechanisms/ResolveComponents/ResolveComponentsViewModel.php
public function doesPageHaveComponents(): bool
{
    return true;
}
```

Do not copy this pattern into frontend theme modules — it would force-boot Magewire on every page including pure-static ones, wasting cycles.

### Admin update URI

Frontend posts to `/magewire/update`. Admin posts to `/{backend_frontname}/magewire/update` (typically `/admin/magewire/update`). The URI is produced by extending the view-model utility:

```php
// magewire-admin/src/Model/View/Utils/Magewire.php
public function getUpdateUri(): string
{
    return '/' .
        $this->deploymentConfig->get(BackendConfigOptionsList::CONFIG_PATH_BACKEND_FRONTNAME) .
        parent::getUpdateUri();
}
```

The rendered script tag consumes this via `data-update-uri`.
