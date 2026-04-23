import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/directives';

test.describe('Magewire Playwright — Directives', () => {
    test.beforeEach(async ({ page }) => {
        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);
    });

    test('renders the page with the correct title', async ({ page }) => {
        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Directives');
    });

    test.describe('Base directives', () => {
        test('every row has a result that matches the expected value', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.directives.base"]');
            await expect(component).toBeVisible();

            const rows = component.locator('tbody tr');
            await expect(rows).toHaveCount(2);

            const count = await rows.count();

            for (let i = 0; i < count; i++) {
                const cells = rows.nth(i).locator('td');
                const [result, expected] = await Promise.all([
                    cells.nth(2).innerText(),
                    cells.nth(3).innerText(),
                ]);

                expect(result.trim()).toBe(expected.trim());
            }
        });

        test('renders the @translate directive with its literal value when escape is false', async ({ page }) => {
            const row = page.locator('[wire\\:id="magewire.playwright.directives.base"] tbody tr').nth(0);

            await expect(row.locator('td').nth(0)).toHaveText('translate');
            await expect(row.locator('td').nth(2)).toHaveText('foo');
        });

        test('renders the @translate directive with an escaped value', async ({ page }) => {
            const row = page.locator('[wire\\:id="magewire.playwright.directives.base"] tbody tr').nth(1);

            await expect(row.locator('td').nth(0)).toHaveText('translate (escaped)');
            await expect(row.locator('td').nth(2)).toHaveText('bar');
        });
    });

    test.describe('Scope directive (@foreach)', () => {
        test('renders one row per iteration with the expected key/value pairs', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.directives.scope"]');
            await expect(component).toBeVisible();

            const rows = component.locator('#scope-directive-foreach tbody tr');
            await expect(rows).toHaveCount(3);

            const expectedPairs = [
                ['0', 'a'],
                ['1', 'b'],
                ['2', 'c'],
            ];

            for (let i = 0; i < expectedPairs.length; i++) {
                const [expectedKey, expectedValue] = expectedPairs[i];
                const cells = rows.nth(i).locator('td');

                await expect(cells.nth(0)).toHaveText(expectedKey);
                await expect(cells.nth(1)).toHaveText(expectedValue);
            }
        });
    });

    test.describe('Render area (@renderChild)', () => {
        test('renders the parent container with its own wire:id', async ({ page }) => {
            const parent = page.locator('[wire\\:id="magewire.playwright.directive.areas.render"]');

            await expect(parent).toBeVisible();
            await expect(parent).toContainText('Parent');
        });

        test('renders the immediate child through @renderChild', async ({ page }) => {
            const child = page.locator('[wire\\:id="magewire.playwright.directive.areas.render.child"]');

            await expect(child).toBeVisible();
            await expect(child).toContainText('Child');
        });

        test('renders the grandchild through a nested @renderChild', async ({ page }) => {
            const grandchild = page.locator('[wire\\:id="magewire.playwright.directive.areas.render.child-child"]');

            await expect(grandchild).toBeVisible();
            await expect(grandchild).toContainText("Child's Child");
        });

        test('nests the parent, child, and grandchild hierarchically', async ({ page }) => {
            const parent = page.locator('[wire\\:id="magewire.playwright.directive.areas.render"]');
            const child = parent.locator('[wire\\:id="magewire.playwright.directive.areas.render.child"]');
            const grandchild = child.locator('[wire\\:id="magewire.playwright.directive.areas.render.child-child"]');

            await expect(parent).toBeVisible();
            await expect(child).toBeVisible();
            await expect(grandchild).toBeVisible();
        });
    });

    test.describe('Escape directive area', () => {
        test('renders the escape area component', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.directive.areas.escape"]');

            await expect(component).toBeAttached();
        });
    });
});