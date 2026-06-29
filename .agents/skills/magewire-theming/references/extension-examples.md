# Extension Examples

Copy-paste starting points for the common theme-module integration tasks. All examples are for a hypothetical `themes/MyTheme/` module with module name `Magewirephp_MagewireCompatibilityWithMyTheme`.

## 1. Bare compatibility module

Use when Magewire works out of the box on the theme and you only need to declare compatibility.

**`themes/MyTheme/registration.php`**
```php
<?php

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Magewirephp_MagewireCompatibilityWithMyTheme',
    __DIR__
);
```

**`themes/MyTheme/etc/module.xml`**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Magewirephp_MagewireCompatibilityWithMyTheme">
        <sequence>
            <module name="Magewirephp_Magewire"/>
        </sequence>
    </module>
</config>
```

## 2. Theme-scoped Feature registered via DI

Use when you need to hook into component lifecycle for this theme only.

**`themes/MyTheme/etc/frontend/di.xml`**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Magewirephp\Magewire\Features">
        <arguments>
            <argument name="items" xsi:type="array">
                <item name="mytheme_feature" xsi:type="array">
                    <item name="type" xsi:type="string">Magewirephp\MagewireCompatibilityWithMyTheme\Magewire\Features\SupportMyTheme\SupportMyTheme</item>
                    <item name="sort_order" xsi:type="number">99100</item>
                    <item name="sequence" xsi:type="array">
                        <item name="magewire_backwards_compatibility" xsi:type="boolean">true</item>
                    </item>
                </item>
            </argument>
        </arguments>
    </type>
</config>
```

**`themes/MyTheme/Magewire/Features/SupportMyTheme/SupportMyTheme.php`**
```php
<?php

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithMyTheme\Magewire\Features\SupportMyTheme;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;

class SupportMyTheme extends ComponentHook
{
    public function hydrate($memo): void
    {
        // Snapshot is being restored — read theme-specific memo flags here.
        if (! isset($memo['mytheme']['flag'])) {
            return;
        }
        // React to the flag.
    }

    public function dehydrate(ComponentContext $context): void
    {
        // Component is serializing — push theme-specific data into the snapshot memo.
        $context->pushMemo('mytheme', true, 'flag');
    }
}
```

Area matters — put the `di.xml` under `etc/frontend/` for storefront themes and `etc/adminhtml/` for backend. Never under `etc/` alone.

## 3. Observer bridging into a theme build event

Use when the target theme publishes an event you can hook to register Magewire paths in its asset pipeline.

**`themes/MyTheme/etc/frontend/events.xml`**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
    <event name="mytheme_config_generate_before">
        <observer name="MagewirephpMagewireCompatibilityWithMyThemeConfigGenerateBefore"
                  instance="Magewirephp\MagewireCompatibilityWithMyTheme\Observer\Frontend\ConfigGenerateBefore"/>
    </event>
</config>
```

**`themes/MyTheme/Observer/Frontend/ConfigGenerateBefore.php`**
```php
<?php

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithMyTheme\Observer\Frontend;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ConfigGenerateBefore implements ObserverInterface
{
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar
    ) {}

    public function execute(Observer $event): void
    {
        $config = $event->getData('config');
        $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];

        $magewirePath = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            'Magewirephp_Magewire'
        );

        if ($magewirePath !== null) {
            $extensions[] = ['src' => substr($magewirePath, strlen(BP) + 1)];
        }

        $config->setData('extensions', $extensions);
    }
}
```

`themes/Hyva/Observer/Frontend/HyvaConfigGenerateBefore.php` is the canonical reference for this pattern.

## 4. Layout override: load Alpine before Magewire

Pattern used by Hyvä. Applies when the target theme ships its own Alpine bundle that must load before Magewire's bundled Alpine hook.

**`themes/MyTheme/view/frontend/layout/default_mytheme.xml`**
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <!-- Reorder theme's Alpine script into Magewire's load slot -->
        <move element="script-alpine-js" destination="magewire.alpinejs.load" before="-"/>

        <!-- Wrap theme's Alpine template so Magewire can decide whether to render it -->
        <referenceBlock name="script-alpine-js"
                        template="Magewirephp_MagewireCompatibilityWithMyTheme::overwrite/MyTheme/page/js/alpinejs.phtml">
            <block name="script-alpine-js-script"
                   template="Magewirephp_MagewireCompatibilityWithMyTheme::script.phtml"
                   after="-">
                <block name="script-alpine-js-original"
                       template="MyTheme::page/js/alpinejs.phtml"/>
            </block>
        </referenceBlock>
    </body>
</page>
```

**`themes/MyTheme/view/frontend/templates/overwrite/MyTheme/page/js/alpinejs.phtml`**
```php
<?php
/**
 * Defers Alpine init — renders child blocks so Magewire's bundled Alpine
 * can take over when it's active, or falls back to the theme's own Alpine
 * when Magewire is disabled on this page.
 */
?>
<?= $block->getChildHtml() ?>
```

**`themes/MyTheme/view/frontend/templates/script.phtml`**
```php
<?php
/** @var \Magento\Framework\View\Element\Template $block */
/** @var \Magento\Framework\Escaper $escaper */
/** @var \Magewirephp\Magewire\ViewModel\Utility $magewireUtility */

$magewireUtility = $block->getData('magewire_utility');
?>
<?php if ($magewireUtility->canRequireMagewireJsLibrary()): ?>
    <script
        id="magewire-script"
        src="<?= $escaper->escapeUrl($magewireUtility->mechanisms()->frontendAssets()->getScriptPath()) ?>"
        x-data="magewireScript"
        x-bind="magewireScriptBindings"
    ></script>
<?php else: ?>
    <?= $block->getChildHtml() ?>
<?php endif ?>
```

See `themes/Hyva/view/frontend/templates/script.phtml` for the production version.

## 5. Registering a Feature-scoped bridge script

Use when your theme Feature needs a companion JS file rendered into `magewire.features`.

**`themes/MyTheme/view/frontend/layout/default_mytheme.xml`**
```xml
<referenceContainer name="magewire.features">
    <block name="magewire.features.support-mytheme"
           template="Magewirephp_MagewireCompatibilityWithMyTheme::magewire-features/support-mytheme/bridge.phtml"/>
</referenceContainer>
```

**`themes/MyTheme/view/frontend/templates/magewire-features/support-mytheme/bridge.phtml`**
```php
<?php
/** @var \Magewirephp\Magewire\ViewModel\Utility $magewireUtility */
/** @var \Magento\Framework\Escaper $escaper */

$magewireUtility = $block->getData('magewire_utility');
$magewireFragment = $magewireUtility->utils()->fragment();
$script = $magewireFragment->make()->script()->start();
?>
<script>
    document.addEventListener('magewire:init', () => {
        Magewire.hook('component.init', ({ component, cleanup }) => {
            // Theme-specific init work here.
        });
    });
</script>
<?php $script->end(); ?>
```

The fragment-based script pattern is non-negotiable: it rewrites inline scripts into CSP-compliant form. Raw `<script>` tags will break on stores with CSP enabled.

## 6. Page-specific Feature activation

Use when a Feature should only boot on one route.

**`themes/MyTheme/view/frontend/layout/mycheckout_index_index.xml`**
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="magewire.features">
            <container name="magewire.features.support-mycheckout">
                <block name="magewire.features.support-mycheckout.components"
                       template="Magewirephp_MagewireCompatibilityWithMyTheme::magewire-features/support-mycheckout/components.phtml"/>
                <block name="magewire.features.support-mycheckout.events"
                       template="Magewirephp_MagewireCompatibilityWithMyTheme::magewire-features/support-mycheckout/events.phtml"/>
            </container>
        </referenceContainer>
    </body>
</page>
```

Group related Feature templates inside a `<container>` so the theme can toggle the whole group with one `<remove>` or `<referenceContainer remove="true">`.

## 7. Adminhtml theme module (lightweight)

Mirror the frontend structure but under `etc/adminhtml/` and `view/adminhtml/`. If the module is adminhtml-only, drop `etc/frontend/` and `view/frontend/` entirely.

**`themes/MyBackendTheme/etc/adminhtml/di.xml`** — same shape as `etc/frontend/di.xml` but the Feature typically hooks admin-only workflows.

**`themes/MyBackendTheme/view/adminhtml/layout/default.xml`** — targets the same container names (`magewire.features`, `magewire.before`, etc.) because they're defined in `src/view/base/layout/default.xml`, which applies to both areas.

See `themes/Hyva/view/adminhtml/layout/default.xml` for a minimal adminhtml example.

For *real* admin integration (custom controller, session validation, plugin-based script injection), see sections 8–12 below — those patterns belong in a standalone package like `magewire-admin`, not in a `themes/` folder.

## 8. Admin update controller with session validation

For a standalone adminhtml package. Reuses the frontend controller's match conditions (POST + URI + JSON content type) and adds admin session validation.

**`src/Controller/MagewireUpdateRouteAdminhtml.php`**
```php
<?php

declare(strict_types=1);

namespace Vendor\YourPackage\Controller;

use Magento\Backend\App\Config as AdminConfig;
use Magento\Backend\Model\Auth\Session\Proxy as AdminSessionProxy;
use Magento\Framework\App\RequestInterface as Request;
use Magewirephp\Magewire\Controller\MagewireUpdateRouteFrontend;

class MagewireUpdateRouteAdminhtml extends MagewireUpdateRouteFrontend
{
    public function __construct(
        private readonly AdminSessionProxy $sessionAuth,
        ...$parentArgs
    ) {
        parent::__construct(...$parentArgs);
    }

    public function getMatchConditions(): array
    {
        return array_merge(parent::getMatchConditions(), [
            'auth' => fn (Request $request): bool =>
                $this->sessionAuth->getSessionId()
                    === $request->getCookie(AdminConfig::SESSION_NAME_ADMIN),
        ]);
    }
}
```

Form-key validation is not added. The combination of admin-session-cookie match + HTTP POST + JSON content type + same-origin is the frontline. If your threat model requires explicit CSRF tokens, add a `form_key` condition that compares the request header against `Magento\Framework\Data\Form\FormKey::getFormKey()`.

**`src/etc/adminhtml/routes.xml`**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
    <router id="admin">
        <route id="magewire" frontName="magewire">
            <module name="Magewirephp_Magewire" before="Magento_Backend"/>
        </route>
    </router>
</config>
```

**`src/etc/adminhtml/di.xml`** (route registration)
```xml
<type name="Magewirephp\Magewire\Controller\Router">
    <arguments>
        <argument name="routes" xsi:type="array">
            <item name="magewire_update_adminhtml"
                  xsi:type="object"
                  sortOrder="20">
                Vendor\YourPackage\Controller\MagewireUpdateRouteAdminhtml
            </item>
        </argument>
    </arguments>
</type>
```

## 9. Page\Config\Renderer plugin for head-phase script injection

Admin renders `<head>` before `<body>`. Layout-XML `move` can't reorder between the head pipeline and RequireJS. A Magento core plugin is the only way.

**`src/Plugin/Magento/Framework/View/Page/Config/Renderer.php`**
```php
<?php

declare(strict_types=1);

namespace Vendor\YourPackage\Plugin\Magento\Framework\View\Page\Config;

use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config\Renderer as Subject;

class Renderer
{
    public function __construct(private readonly LayoutInterface $layout) {}

    public function afterRenderAssets(Subject $subject, string $result): string
    {
        $head = $this->layout->getBlock('magewire.head');
        if ($head === false) {
            return $result;
        }

        return preg_replace(
            '/(<script\b[^>]*>)/i',
            $head->toHtml() . '$1',
            $result,
            1
        );
    }
}
```

Register in `src/etc/adminhtml/di.xml`:

```xml
<type name="Magento\Framework\View\Page\Config\Renderer">
    <plugin name="magewire_admin_head_injection"
            type="Vendor\YourPackage\Plugin\Magento\Framework\View\Page\Config\Renderer"
            sortOrder="10"/>
</type>
```

The regex injects once before the *first* `<script>`. If admin theming ever adds a `<script>` before any asset the Renderer handles, this might misfire — scope it tighter by matching on a known RequireJS script or include a sentinel comment in the layout head.

## 10. RequireJS module to patch Prototype.js pollution

Magento admin still loads Prototype.js, which adds enumerable properties to `Object.prototype`. This corrupts Magewire's child-component discovery (it iterates object keys to find nested components).

**`src/view/adminhtml/requirejs-config.js`**
```javascript
var config = {
    deps: [
        'Vendor_YourPackage/js/fix-prototype-object-pollution'
    ]
};
```

**`src/view/adminhtml/web/js/fix-prototype-object-pollution.js`**
```javascript
define(['prototype'], function () {
    'use strict';

    const originalKeys = Object.keys;
    const originalValues = Object.values;

    Object.keys = function (obj) {
        return originalKeys(obj).filter(key => Object.prototype.hasOwnProperty.call(obj, key));
    };

    Object.values = function (obj) {
        return Object.keys(obj).map(key => obj[key]);
    };
});
```

The `['prototype']` dep guarantees this runs after Prototype.js has done its damage. Without this shim, Magewire child-component traversal in admin will iterate Prototype's injected methods as if they were child components and throw.

## 11. Admin component resolver

Declares a distinct accessor (`layout_admin`) so admin layout XML can bind components without colliding with frontend's `layout` accessor.

**`src/Magewire/Mechanisms/ResolveComponents/ComponentResolver/LayoutAdminResolver.php`**
```php
<?php

declare(strict_types=1);

namespace Vendor\YourPackage\Magewire\Mechanisms\ResolveComponents\ComponentResolver;

use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\LayoutResolver;

class LayoutAdminResolver extends LayoutResolver
{
    protected string $accessor = 'layout_admin';
}
```

**`src/etc/adminhtml/di.xml`**
```xml
<type name="Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolverManagement">
    <arguments>
        <argument name="resolvers" xsi:type="array">
            <item name="layout_admin" xsi:type="object" sortOrder="99800">
                Vendor\YourPackage\Magewire\Mechanisms\ResolveComponents\ComponentResolver\LayoutAdminResolver
            </item>
        </argument>
    </arguments>
</type>
```

Sort order `99800` runs before the default `99900` so admin-accessor components resolve first. Admin components then declare:

```xml
<block name="admin.my.component" class="..." template="...">
    <arguments>
        <argument name="magewire" xsi:type="string">layout_admin</argument>
    </arguments>
</block>
```

## 12. Update URI prefix override

Admin endpoints live under the backend frontname. The view-model utility that produces the `data-update-uri` attribute on the Magewire script tag needs to prefix the URI.

**`src/Model/View/Utils/Magewire.php`**
```php
<?php

declare(strict_types=1);

namespace Vendor\YourPackage\Model\View\Utils;

use Magento\Backend\Setup\ConfigOptionsList as BackendConfigOptionsList;
use Magento\Framework\App\DeploymentConfig;
use Magewirephp\Magewire\Model\View\Utils\Magewire as ParentUtility;

class Magewire extends ParentUtility
{
    public function __construct(
        private readonly DeploymentConfig $deploymentConfig,
        ...$parentArgs
    ) {
        parent::__construct(...$parentArgs);
    }

    public function getUpdateUri(): string
    {
        return '/'
            . $this->deploymentConfig->get(BackendConfigOptionsList::CONFIG_PATH_BACKEND_FRONTNAME)
            . parent::getUpdateUri();
    }
}
```

Wire it via DI in `src/etc/adminhtml/di.xml`:

```xml
<preference for="Magewirephp\Magewire\Model\View\Utils\Magewire"
            type="Vendor\YourPackage\Model\View\Utils\Magewire"/>
```

Preference is area-scoped — frontend keeps the original, admin gets the prefixed version.

## 13. `ResolveComponentsViewModel` override

Admin head-phase rendering evaluates "does this page have components?" before body components exist. Force-return `true` so Mechanisms and Features boot unconditionally in admin.

**`src/Magewire/Mechanisms/ResolveComponents/ResolveComponentsViewModel.php`**
```php
<?php

declare(strict_types=1);

namespace Vendor\YourPackage\Magewire\Mechanisms\ResolveComponents;

use Magewirephp\Magewire\Mechanisms\ResolveComponents\ResolveComponentsViewModel as ParentViewModel;

class ResolveComponentsViewModel extends ParentViewModel
{
    public function doesPageHaveComponents(): bool
    {
        return true;
    }
}
```

Bind via `<preference>` in `src/etc/adminhtml/di.xml`. This is a known workaround, not a design. Track it — when Magewire grows a head-aware alternative, drop this override.
