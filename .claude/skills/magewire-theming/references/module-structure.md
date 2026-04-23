# Theme Module Structure

A Magewire theme compatibility module is a standard Magento 2 module that lives under `themes/{Theme}/` inside the `magewirephp/magewire` package. It ships with the core package — it is not a separate composer install.

## Minimum viable module

Every theme module needs exactly these two files plus a PSR-4 autoload entry in the root `composer.json`.

```
themes/MyTheme/
├── registration.php
└── etc/
    └── module.xml
```

### `registration.php`

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

### `etc/module.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Magewirephp_MagewireCompatibilityWithMyTheme">
        <sequence>
            <module name="Magewirephp_Magewire"/>
            <!-- Optional: the theme module(s) this compatibility layer targets -->
            <!-- <module name="MyVendor_MyTheme"/> -->
        </sequence>
    </module>
</config>
```

The `<sequence>` entry for `Magewirephp_Magewire` is load-bearing — without it, the core's DI and layout may boot after this module, and layout references will fail silently.

If your compatibility layer targets a specific Magento theme module (e.g. `Hyva_Theme`), include it in the sequence so that theme's templates and layouts exist before yours tries to override them.

### PSR-4 entry in root `composer.json`

The root `magewirephp/magewire` composer file maps theme namespaces. Add your module:

```json
{
  "autoload": {
    "psr-4": {
      "Magewirephp\\MagewireCompatibilityWithMyTheme\\": "themes/MyTheme/"
    }
  }
}
```

Run `composer dump-autoload` after adding the mapping.

## Naming conventions

| Thing | Convention | Example |
|-------|-----------|---------|
| Directory | `themes/{Theme}` (PascalCase, no vendor prefix) | `themes/Hyva/` |
| Module name | `Magewirephp_MagewireCompatibilityWith{Theme}` | `Magewirephp_MagewireCompatibilityWithHyva` |
| PHP namespace | `Magewirephp\MagewireCompatibilityWith{Theme}\` | `Magewirephp\MagewireCompatibilityWithHyva\` |
| Module alias in layout XML handle | `default_{lowercase_theme}.xml` | `default_hyva.xml` |
| Template vendor prefix | `Magewirephp_MagewireCompatibilityWith{Theme}::` | `Magewirephp_MagewireCompatibilityWithHyva::script.phtml` |

## Optional directories

Add only what the theme actually needs.

```
themes/MyTheme/
├── registration.php
├── etc/
│   ├── module.xml
│   ├── frontend/
│   │   ├── di.xml           # Register theme-scoped Features
│   │   └── events.xml       # Observe theme build events (e.g. hyva_config_generate_before)
│   └── adminhtml/
│       └── di.xml           # Adminhtml Features (only for backend themes)
├── Magewire/
│   └── Features/
│       └── Support.../      # ComponentHook subclasses scoped to this theme
├── Observer/
│   └── Frontend/
│       └── *.php            # Bridge into theme build pipelines
└── view/
    ├── base/
    │   └── templates/       # Shared across frontend + adminhtml
    ├── frontend/
    │   ├── layout/
    │   │   ├── default_{theme}.xml       # Global theme overrides
    │   │   └── {route}_{action}.xml      # Page-specific wiring
    │   ├── templates/
    │   │   ├── magewire-features/        # Feature-specific PHTML
    │   │   ├── overwrite/{Target}/       # Overrides of other modules' templates
    │   │   ├── js/                       # Theme-scoped JS PHTML
    │   │   └── script.phtml
    │   └── tailwind/
    │       ├── tailwind.config.js        # Theme-only Tailwind content globs
    │       ├── module.css                # Imports core via @source
    │       └── ui-components/*.css
    └── adminhtml/
        └── layout/
            └── default.xml
```

## Decision matrix

| Goal | Files |
|------|-------|
| "Magewire works on Luma" statement | `registration.php` + `etc/module.xml` |
| "Load Alpine before Magewire on Hyvä" | + `view/frontend/layout/default_{theme}.xml` |
| "Run my BC hook only when a component renders under this theme's checkout" | + `etc/frontend/di.xml` + `Magewire/Features/Support.../*.php` |
| "Scan my theme's PHTML with Tailwind" | + `view/frontend/tailwind/*` |
| "Tell the theme's asset pipeline where Magewire's templates are" | + `Observer/Frontend/*.php` + `etc/frontend/events.xml` |
| "Serve a theme-specific flash-message bridge" | + `view/frontend/templates/magewire-features/*.phtml` + layout reference |

## Reference implementations

- Deepest in-tree integration: `themes/Hyva/` — BC features, observers, template overrides, Tailwind wiring
- Bare minimum (markers): `themes/Luma/`, `themes/Breeze/`, `themes/Backend/` — registration + module.xml only
- Standalone admin integration: `magewirephp/magewire-admin` (separate composer package) — controllers, plugins, custom resolver, RequireJS workaround

When you copy from Hyvä, strip out what isn't needed for your theme. Do not ship empty containers or unused observer classes.

## Standalone admin-integration package

Adminhtml integration doesn't fit the `themes/{Theme}/` mould. The canonical package is `magewirephp/magewire-admin` — it ships outside the main magewire repo and is required separately.

### When to use standalone over in-tree

| Trigger | Choose |
|---------|--------|
| Theme ships own CSS pipeline, different build config, or overrides Magewire layout containers | In-tree `themes/{Theme}/` |
| Target is adminhtml, requires custom controller, admin session validation, or plugin on Magento core classes | Standalone package |
| Need per-release versioning decoupled from main magewire cadence | Standalone package |
| Target is experimental or third-party, unsuitable for the main repo | Standalone package |

### Standalone package shape (based on `magewire-admin`)

```
magewirephp/magewire-admin/
├── composer.json                        # type: magento2-module
├── README.md
└── src/
    ├── registration.php                 # module name: Magewirephp_MagewireAdmin
    ├── Controller/
    │   └── MagewireUpdateRouteAdminhtml.php   # extends frontend controller + admin session check
    ├── Magewire/
    │   └── Mechanisms/
    │       └── ResolveComponents/
    │           ├── ComponentResolver/
    │           │   └── LayoutAdminResolver.php     # accessor: layout_admin
    │           └── ResolveComponentsViewModel.php  # overrides doesPageHaveComponents() → true
    ├── Model/
    │   └── View/Utils/Magewire.php      # prefixes update URI with backend frontname
    ├── Plugin/
    │   └── Magento/Framework/View/Page/Config/Renderer.php   # injects head block before first <script>
    ├── etc/
    │   ├── module.xml                   # sequence: Magento_Backend, Magewirephp_Magewire
    │   └── adminhtml/
    │       ├── di.xml                   # registers resolver + custom route
    │       └── routes.xml               # router: admin, frontName: magewire
    └── view/
        └── adminhtml/
            ├── requirejs-config.js      # loads Prototype.js pollution fix as global dep
            ├── layout/default.xml       # creates magewire.head container, moves magewire into root
            ├── templates/
            │   ├── dashboard/magewire.phtml
            │   └── js/magewire/{head.phtml, head/script.phtml}
            └── web/
                └── js/fix-prototype-object-pollution.js   # restores Object.keys/values
```

### Standalone `composer.json` template

```json
{
    "name": "vendor/your-package",
    "type": "magento2-module",
    "description": "Magewire integration for X",
    "license": "MIT",
    "require": {
        "php": ">=8.1",
        "magento/framework": "*",
        "magewirephp/magewire": "^3.0"
    },
    "autoload": {
        "files": ["src/registration.php"],
        "psr-4": {
            "Vendor\\YourPackage\\": "src/"
        }
    }
}
```

Always pin a minimum `magewirephp/magewire` version. The current `magewire-admin` `composer.json` omits this — that's a trap for future upgraders.

### What is NOT in a theme module (in-tree)

Do not put the following in `themes/{Theme}/` — they belong in a standalone package:

- Custom controllers extending Magewire's update controller
- Plugins on Magento core classes (`Page\Config\Renderer`, router, session)
- Custom Mechanisms or Component Resolvers
- View Model overrides of core Magewire view models
- RequireJS config files (JS bundle management)

`themes/Hyva/` gets away with an observer (`HyvaConfigGenerateBefore`) because it hooks a theme-owned event, not a Magento core class. That's the line.
