import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/placements';

test.describe('Magewire Playwright — Placements', () => {
    test.beforeEach(async ({ page }) => {
        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);
    });

    test('renders the page with the correct title', async ({ page }) => {
        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Placements');
    });

    test('runs inline and placed scripts', async ({ page }) => {
        await expect(page.locator('[data-placement-result="inline"]')).toHaveText('inline');
        await expect(page.locator('[data-placement-result="default"]')).toHaveText('default');
    });

    test('moves placement scripts and links source and placement comments', async ({ page }) => {
        const html = await page.content();
        const source = html.match(/<!-- Magewire: Script "([A-Z]+)" transferred to "default"\. -->/);

        expect(source).not.toBeNull();
        expect(source[1]).toHaveLength(8);
        expect(html).toContain(`<!-- Magewire: Script placement "${source[1]}". -->`);
    });

    test('keeps non-placed scripts inline', async ({ page }) => {
        const html = await page.content();
        const inlineIndex = html.indexOf('data-placement-script="inline"');
        const sourceIndex = html.indexOf('Magewire: Script "');

        expect(inlineIndex).toBeGreaterThan(-1);
        expect(sourceIndex).toBeGreaterThan(-1);
        expect(inlineIndex).toBeLessThan(sourceIndex);
    });
});
