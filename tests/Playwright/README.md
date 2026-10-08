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

## Store-view CI coverage

The latest Mage-OS CI job creates `magewire_en` and `magewire_nl` after the regular suite,
then runs `tests/store-views.spec.js` separately. It covers store codes and URL rewrites both
enabled and disabled. Each case switches English → Dutch → English in one browser session,
verifies the rendered update URI, and makes two real component updates per page. The checks
require HTTP 200 JSON without redirects and the selected store view in the server-rendered
component response. With store codes enabled, a conflicting store cookie proves the URL takes
precedence; with codes disabled, the cookie selects the view.

This suite changes global URL configuration and is skipped during normal test runs. To run it
on a disposable Magento installation with those two store views and the Hyvä theme configured:

```sh
MAGENTO_ROOT=/path/to/magento MAGEWIRE_MULTI_STORE_VIEW=1 npx playwright test tests/store-views.spec.js --workers=1
```

## UI workbench

Open `/magewire/playwright/ui` in a non-production Magento installation to inspect and style the
core UI without having to reproduce application states elsewhere. The workbench uses the real
notifier, loading icon, exception template, and Magewire directives. Its controls can keep every
notification visible, let notifications expire, replay all message types, or clear the preview.

The same page is covered by `tests/ui.spec.js`, including persistent and expiring notifications,
occurrence badges, loading, dirty, and offline behavior. Theme compatibility modules can visit this
route in their own Playwright suite to verify their presentation overrides against the same markup.

## More details

For more information about Playwright, please refer to [the documentation](https://playwright.dev/docs/intro).
