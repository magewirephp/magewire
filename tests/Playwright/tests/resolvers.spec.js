import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/resolvers';

/**
 * Read and JSON-parse the `wire:snapshot` attribute of a locator.
 */
async function readSnapshot(locator) {
    const raw = await locator.getAttribute('wire:snapshot');
    if (raw === null) {
        throw new Error('wire:snapshot attribute not present on locator');
    }
    return JSON.parse(raw);
}

test.describe('Magewire Playwright — Resolvers', () => {
    test.beforeEach(async ({ page }) => {
        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);
    });

    test('renders the page with the correct title', async ({ page }) => {
        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Resolvers');
    });

    test.describe('LayoutResolver (default)', () => {
        test('mounts a component whose wire:id matches the layout block name', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.default"]');
            await expect(component).toBeVisible();
        });

        test('tags the snapshot memo with the "layout" resolver accessor', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.default"]');
            const snapshot = await readSnapshot(component);

            expect(snapshot.memo.resolver).toBe('layout');
        });

        test('uses the block name as both the memo id and memo name', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.default"]');
            const snapshot = await readSnapshot(component);

            expect(snapshot.memo.id).toBe('magewire.playwright.resolvers.layout.default');
            expect(snapshot.memo.name).toBe('magewire.playwright.resolvers.layout.default');
        });

        test('exposes the active layout handles in the snapshot memo', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.default"]');
            const snapshot = await readSnapshot(component);

            expect(Array.isArray(snapshot.memo.handles)).toBe(true);
            expect(snapshot.memo.handles).toContain('magewire_playwright_resolvers');
        });

        test('excludes the Magento "default" handle from the snapshot memo', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.default"]');
            const snapshot = await readSnapshot(component);

            expect(snapshot.memo.handles).not.toContain('default');
        });
    });

    test.describe('LayoutResolver — aliasing', () => {
        test('propagates the magewire:alias block data into the snapshot memo', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.aliased"]');
            const snapshot = await readSnapshot(component);

            expect(snapshot.memo.alias).toBe('resolver-alias-example');
        });

        test('omits the alias memo entry when no alias is configured', async ({ page }) => {
            const component = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.default"]');
            const snapshot = await readSnapshot(component);

            expect(snapshot.memo.alias ?? null).toBeNull();
        });
    });

    test.describe('LayoutResolver — nested components', () => {
        test('resolves the parent and child as independent components with distinct wire:ids', async ({ page }) => {
            const parent = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.nested"]');
            const child = parent.locator('[wire\\:id="magewire.playwright.resolvers.layout.nested.child"]');

            await expect(parent).toBeVisible();
            await expect(child).toBeVisible();
        });

        test('each nested component carries the "layout" resolver accessor in its memo', async ({ page }) => {
            const parent = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.nested"]');
            const child = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.nested.child"]');

            const [parentSnapshot, childSnapshot] = await Promise.all([
                readSnapshot(parent),
                readSnapshot(child),
            ]);

            expect(parentSnapshot.memo.resolver).toBe('layout');
            expect(childSnapshot.memo.resolver).toBe('layout');
        });

        test('records the child component in the parent memo children map', async ({ page }) => {
            const parent = page.locator('[wire\\:id="magewire.playwright.resolvers.layout.nested"]');
            const snapshot = await readSnapshot(parent);

            expect(snapshot.memo.children).toBeTruthy();

            const childNames = Object.values(snapshot.memo.children).map(([, name]) => name);
            expect(childNames).toContain('magewire.playwright.resolvers.layout.nested.child');
        });
    });
});