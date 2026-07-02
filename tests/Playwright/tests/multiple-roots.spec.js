import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/multipleroots';

test.describe('Magewire Playwright — Multiple Root Element Detection', () => {
    test.beforeEach(async ({ page }) => {
        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);
    });

    test('renders the page with the correct title', async ({ page }) => {
        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Multiple Roots');
    });

    test.describe('Component with multiple root elements', () => {
        test('renders the inline Magewire exception block instead of the component', async ({ page }) => {
            const exception = page.locator('.magewire-exception');

            await expect(exception).toBeVisible();
            await expect(exception).toContainText(
                'Magewire only supports a single root element per component',
            );
        });

        test('names the offending template in the exception message', async ({ page }) => {
            const exception = page.locator('.magewire-exception');

            await expect(exception).toContainText('multiple_roots/invalid.phtml');
        });

        test('does not mount the broken markup as a live component', async ({ page }) => {
            // The second root never receives wire:id/wire:snapshot, so no live
            // component should exist for the invalid block.
            const component = page.locator(
                '[wire\\:id="magewire.playwright.multiple_roots.invalid"]',
            );

            await expect(component).toHaveCount(0);
        });
    });

    test.describe('Component with a single root element', () => {
        test('renders untouched as a live component', async ({ page }) => {
            const component = page.locator(
                '[wire\\:id="magewire.playwright.multiple_roots.valid"]',
            );

            // wire:id is injected onto the single root <div>, so the component
            // locator and #multiple-roots-valid resolve to the same element.
            await expect(component).toBeVisible();
            await expect(component).toHaveAttribute('id', 'multiple-roots-valid');
            await expect(component).toContainText('Hello World');
        });

        test('does not trigger an exception block', async ({ page }) => {
            const valid = page.locator(
                '[wire\\:id="magewire.playwright.multiple_roots.valid"] .magewire-exception',
            );

            await expect(valid).toHaveCount(0);
        });
    });
});