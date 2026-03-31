# Features & Extensions

## Extend `ComponentHook`

Every Feature is a `ComponentHook` subclass that hooks into the component lifecycle via `provide()`. Implement only the hooks you need.

```php
namespace Vendor\Module\Magewire\Features;

use Magewirephp\Magewire\ComponentHook;
use function Magewirephp\Magewire\on;

class SupportMyFeature extends ComponentHook
{
    public static function provide(): void
    {
        on('mount', function ($params) {
            // Access component via $this->component
            // Return a callback for post-processing, or nothing
        });

        on('dehydrate', function ($context) {
            // Push effects to the frontend
            $context->pushEffect('myEffect', $this->collectData());
        });
    }
}
```

## Follow Naming Conventions

| Prefix | Meaning | Example |
|--------|---------|---------|
| `SupportMagewire*` | Magewire-specific feature | `SupportMagewireLoaders`, `SupportMagewireNotifications` |
| `SupportMagento*` | Magento bridge/integration | `SupportMagentoLayouts`, `SupportMagentoFlashMessages` |
| `Support*` | Ported from Livewire | `SupportEvents`, `SupportAttributes`, `SupportRedirects` |

Third-party features should use `SupportMagewire*` or `SupportMagento*` depending on whether they extend Magewire functionality or bridge a Magento subsystem.

## Use `storeSet()` / `storeGet()` for Feature State

Features should not use class properties for component-scoped state — the same Feature instance may serve multiple components. Use the component-scoped data store instead.

```php
on('mount', function () {
    // Store data scoped to this specific component
    $this->storeSet('initialized', true);
});

on('dehydrate', function ($context) {
    if ($this->storeGet('initialized')) {
        $context->pushEffect('myFeature', ['ready' => true]);
    }
});
```

## Push Effects During `dehydrate()`

Side effects (data sent to the frontend) must be pushed via `$context->pushEffect()` during the dehydrate phase — not by echoing output or modifying the response directly.

```php
on('dehydrate', function ($context) {
    $messages = $this->collectPendingMessages();

    if (!empty($messages)) {
        $context->pushEffect('notifications', $messages);
    }
});
```

The frontend feature bridge script then reads effects from the commit response:

```javascript
Magewire.hook('commit', function({ succeed }) {
    succeed(function({ effects }) {
        if (effects.notifications) {
            window.MagewireAddons.notifier.create(effects.notifications);
        }
    });
});
```

## Feature-Owned Assets Live in the Feature Folder

When a Feature has its own JavaScript, Alpine component, or HTML template, place them inside the feature's directory — not in the global `addons/`, `components/`, or `ui-components/` directories.

```
js/magewire/features/support-my-feature/
├── support-my-feature.phtml      ← Primary bridge script
├── addon.phtml                   ← Feature-owned addon (if needed)
└── component.phtml               ← Feature-owned Alpine component (if needed)
```

Global `addons/` and `alpinejs/components/` are reserved for standalone components that work independently of any specific Feature.

## Register via Layout as Child Blocks

Feature scripts go in the `magewire.features` container. If a feature has multiple PHTML files, register the primary as the parent and extras as child blocks.

```xml
<referenceContainer name="magewire.features">
    <block name="magewire.features.support-my-feature"
           template="Vendor_Module::js/magewire/features/support-my-feature/support-my-feature.phtml">
        <block name="magewire.features.support-my-feature.addon"
               as="addon"
               template="Vendor_Module::js/magewire/features/support-my-feature/addon.phtml"/>
    </block>
</referenceContainer>
```

The primary PHTML renders children first, then its own bridge script:

```php
<?= $block->getChildHtml('addon') ?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    // Bridge script
</script>
<?php $script->end() ?>
```

## Facades for Public APIs

When a Feature or Mechanism needs a public API that other modules consume, register a Facade via DI.

```xml
<item name="my_feature" xsi:type="array">
    <item name="type" xsi:type="string">Vendor\Module\Features\SupportMyFeature</item>
    <item name="facade" xsi:type="string">Vendor\Module\Features\SupportMyFeature\MyFeatureFacade</item>
    <item name="sort_order" xsi:type="number">5050</item>
</item>
```

Access via the service provider:

```php
$facade = $magewireServiceProvider->getMyFeatureFacade();
```

Facades live in their feature's subdirectory. This pattern is experimental — check the `magewire-architecture` skill for current status.
