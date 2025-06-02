import { expect } from '@playwright/test'
import { test } from '../fixtures/frontend/customer.js';
import { logout } from "../helpers/customer/account";

test.describe('Directives', () => {
    /*
     * Runs before each individual test.
     */
    test.beforeEach(async ({ page, magentoCustomerDashboard }) => {
        const pageVersion = Math.floor(Math.random() * 10000);

        await magentoCustomerDashboard.goto(`/magewire/playwright/directives?v=${pageVersion}`);
    });

    /*
     * Runs after each individual test.
     */
    test.afterEach(async ({ page }) => {
        await logout(page)
    });

    test('Check Page Title', async ({ page }) => {
        const title = page.locator('[data-ui-id="page-title-wrapper"]');

        await expect(title).toHaveText('Magewire / Playwright / Directives');
    })

    test('Check Base Directives', async ({ page }) => {
        const table = page.locator('[wire\\:id="magewire.playwright.directives.base"]');
        await expect(table).toBeVisible();

        const rows = await table.locator('tbody tr').all();
        expect(rows).toHaveLength(2)

        for (const row of rows) {
            const cells = await row.locator('td').allInnerTexts();

            expect(cells[2]).toBe(cells[3]);
        }
    });
});
