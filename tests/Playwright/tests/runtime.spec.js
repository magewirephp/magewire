import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/directives';

async function mountRuntimeProbe(page, dataProvider, bindingProvider) {
    return page.evaluate(({ dataProvider, bindingProvider }) => {
        const probe = document.createElement('div');
        probe.setAttribute('x-data', dataProvider);
        probe.setAttribute('x-bind', bindingProvider);
        document.body.appendChild(probe);

        window.Alpine.initTree(probe);

        const attributes = {
            csrf: probe.getAttribute('data-csrf'),
            updateUri: probe.getAttribute('data-update-uri'),
        };

        probe.remove();

        return attributes;
    }, { dataProvider, bindingProvider });
}

test.describe('Magewire Playwright — Runtime', () => {
    test.beforeEach(async ({ page }) => {
        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);
        await page.waitForFunction(() => window.Alpine && window.MagewireUtilities);
    });

    test('registers the canonical runtime providers', async ({ page }) => {
        const attributes = await mountRuntimeProbe(
            page,
            'magewireRuntime',
            'magewireRuntimeBindings',
        );

        expect(attributes.csrf).toMatch(/\S/);
        expect(attributes.updateUri).toMatch(/\/magewire\/update/);
    });

    test('keeps the deprecated script providers operational', async ({ page }) => {
        const attributes = await mountRuntimeProbe(
            page,
            'magewireScript',
            'magewireScriptBindings',
        );

        expect(attributes.csrf).toMatch(/\S/);
        expect(attributes.updateUri).toMatch(/\/magewire\/update/);
    });
});
