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

## More details

For more information about Playwright, please refer to [the documentation](https://playwright.dev/docs/intro).
