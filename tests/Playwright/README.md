# Playwright

## Requirements

- Magento Sample Data
- Magento developer mode

## Install

To run the Magewire Playwright tests, follow these steps:

1. Navigate into the Playwright tests directory:
   ```sh
   cd tests/Playwright
   ```

2. Install all dev-dependencies
   ```sh
   npm install
   ```

3. Create a `.env` config file in the root `Playwright` folder using the following variables:
   ```text
   BASE_URL=https://local.test/
   
   ENVIRONMENT=local
   ACCOUNT_FIRSTNAME=Veronica
   ACCOUNT_LASTNAME=Costello
   ACCOUNT_EMAIL=roni_cost@example.com
   ACCOUNT_PASSWORD=roni_cost3@example.com
   ```
   _Set the `BASE_URL` value with the `base-url` of your Magento instance._


4. Run tests
   ```sh
   npm run test
   ```

5. Run tests manually (optional)
   ```sh
   npx playwright test --ui
   ```

## UI workbench

Open `/magewire/playwright/ui` in a non-production Magento installation to inspect and style the
core UI without having to reproduce application states elsewhere. The workbench uses the real
notifier, loading icon, exception template, and Magewire directives. Its controls can keep every
notification visible, let notifications expire, replay all message types, or clear the preview.

The same page is covered by `tests/ui.spec.js`, including persistent and expiring notifications,
occurrence badges, loading, dirty, and offline behavior. Theme compatibility modules can visit this
route in their own Playwright suite to verify their presentation overrides against the same markup.

## Exception regression and production checks

The complex-message and environment tests use a separate fixture module under
`fixtures/Magewirephp/MagewirePlaywright`. Magewire does not register it during a normal installation.
It deliberately throws exceptions containing private diagnostic markers and provides a JSON endpoint
that queues a complex flash message. Install it only in an isolated test store:

```sh
mkdir -p "$MAGENTO_ROOT/app/code/Magewirephp"
cp -R fixtures/Magewirephp/MagewirePlaywright "$MAGENTO_ROOT/app/code/Magewirephp/"
php "$MAGENTO_ROOT/bin/magento" module:enable Magewirephp_MagewirePlaywright
php "$MAGENTO_ROOT/bin/magento" setup:upgrade
MAGEWIRE_PLAYWRIGHT_FIXTURES=1 npx playwright test tests/exceptions.spec.js tests/exceptions-environments.spec.js
```

The normal config expects developer mode. The environment tests verify the mode reported by a healthy
fixture page, then trigger both `Exception` and `TypeError` during page rendering and component updates.
They inspect the raw response and the browser's presentation, including Magewire's error iframe.
Page failures are raised after layout rendering, outside Magento's block exception handler, so they
exercise HTTP error handling in both modes.

After the developer suite finishes, switch that isolated store to production and run the same checks:

```sh
php "$MAGENTO_ROOT/bin/magento" deploy:mode:set production
MAGEWIRE_PLAYWRIGHT_FIXTURES=1 npx playwright test --config playwright.exceptions-production.config.js
```

Production must return a generic HTTP 500 without the private markers, source paths, exception class
names, or stack traces. A control also verifies that queued complex messages render and component
updates succeed in both modes. CI installs the fixture and runs these two phases sequentially; no
Playwright worker changes the store mode. Without the fixture flag, these optional local checks skip.

## More details

For more information about Playwright, please refer to [the documentation](https://playwright.dev/docs/intro).
