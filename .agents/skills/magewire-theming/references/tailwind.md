# Tailwind Pipeline

Magewire compiles one base stylesheet (`src/view/base/web/css/magewire.css`) using Tailwind v4. Theme modules extend this pipeline by scanning their own templates and, optionally, overriding CSS variables.

## Core pipeline

### Config

**`src/view/frontend/tailwind/tailwind.config.js`**
```js
module.exports = {
  content: [
    '../../../../src/view/base/layout/**/*.xml',
    '../../../../src/view/base/templates/**/*.phtml',
    '../../../../src/view/frontend/layout/**/*.xml',
    '../../../../src/view/frontend/templates/**/*.phtml',
  ]
};
```

### Build

```bash
npm install
npx @tailwindcss/cli \
  -i ./styles/magewire.css \
  -o ./src/view/base/web/css/magewire.css \
  --optimize
```

This compiles to `src/view/base/web/css/magewire.css` — a flat CSS file Magento serves as a static asset.

## Theme-scoped Tailwind config

When a theme adds its own PHTML templates with Tailwind classes, those classes must be scanned during build. Each theme module can ship its own Tailwind config.

### `themes/{Theme}/view/frontend/tailwind/tailwind.config.js`

```js
module.exports = {
  content: [
    '../../../../themes/{Theme}/view/frontend/templates/**/*.phtml'
  ]
};
```

The path is relative from the CSS entry file. Verify by running the CLI from the theme directory and checking that theme-only class names appear in the output.

### `themes/{Theme}/view/frontend/tailwind/module.css`

```css
@source "../../../../../src/view";
@import "./ui-components/notifier.css";
```

`@source` is Tailwind v4's inheritance directive — it imports the scan set from the core package so the theme gets the same utility classes plus its own. Without `@source`, the theme's Tailwind build will strip all classes Magewire relies on.

## CSS variable overrides

Magewire's CSS exposes theming hooks through CSS custom properties. For color/spacing changes, overriding variables beats recompiling.

```css
/* themes/{Theme}/view/frontend/web/css/overrides.css */
.magewire-notifier__notification {
  --notifier-bg: #f0f9ff;
  --notifier-border: #0284c7;
  --notifier-title: #075985;
}

.magewire-notifier__notification--success {
  --notifier-bg: #ecfdf5;
  --notifier-border: #059669;
  --notifier-title: #047857;
}
```

See `src/view/base/web/css/magewire.css` lines 638–800 for the full list of supported variables on the notifier and related components.

## Hyvä Tailwind integration

Hyvä's Tailwind build scans all registered modules listed in its `extensions` config. Magewire hooks `hyva_config_generate_before` so its paths are injected automatically:

**`themes/Hyva/Observer/Frontend/HyvaConfigGenerateBefore.php`** (simplified):

```php
public function execute(Observer $event): void
{
    $config = $event->getData('config');
    $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];

    $magewirePath = $this->componentRegistrar->getPath(
        ComponentRegistrar::MODULE,
        'Magewirephp_Magewire'
    );

    $extensions[] = ['src' => substr($magewirePath, strlen(BP) + 1)];

    $config->setData('extensions', $extensions);
}
```

This tells Hyvä's Tailwind compiler to scan Magewire's templates alongside the theme's own. Without this observer, Magewire-authored classes would be stripped as "unused" during Hyvä's build.

When creating a new theme compat module that targets a Hyvä-like build pipeline (any theme that scans a curated list of modules rather than all vendor/), copy this observer pattern.

## When not to use Tailwind

- Backend themes (`themes/Backend/`) don't need Tailwind — adminhtml UI is styled by Magento's own CSS.
- Luma/Breeze already compile CSS via their own LESS/SCSS toolchains. A Magewire theme compat module for those typically does not add a Tailwind config — instead, it ships prebuilt CSS or uses CSS variables.

## Debug checklist

If Tailwind classes aren't applying in a theme:

1. Confirm the theme's `tailwind.config.js` `content` globs actually match your PHTML paths.
2. Confirm `@source` in `module.css` points at `src/view` (relative paths bite here).
3. Rebuild: `npx @tailwindcss/cli -i ... -o ...`.
4. Check the compiled CSS file for the missing class name. If it's absent, scanning is broken. If present, it's a precedence or caching issue.
5. Flush Magento's `var/view_preprocessed` and `pub/static` caches; re-deploy static content.
