import { test, expect } from '@playwright/test';
import { fixturesEnabled, complexMessage, queueComplexMessage } from '../helpers/exception-fixtures.js';

const PATH = '/magewirefixture';
const PRIVATE_MARKER = 'MAGEWIRE_PLAYWRIGHT_PRIVATE_DETAIL';
const PRIVATE_PATH = '/private/magewire-playwright/source.php';
const GENERIC_ERROR = /An error has happened during application run|There has been an error processing your request/i;

function assertDiagnostics(content, mode) {
    if (mode === 'developer') {
        expect(content).toContain(PRIVATE_MARKER);
        expect(content).toContain(PRIVATE_PATH);
        return;
    }

    expect(content).toMatch(GENERIC_ERROR);
    for (const detail of [PRIVATE_MARKER, PRIVATE_PATH, 'Model/Failure.php', '/app/code/', '/vendor/',
        'Magewirephp\\MagewirePlaywright', 'RuntimeException', 'TypeError', 'Stack trace:']) {
        expect(content).not.toContain(detail);
    }
}

test.describe('Magewire exception responses by environment', () => {
    test.skip(!fixturesEnabled, 'Requires the explicitly installed Magewirephp_MagewirePlaywright fixture module.');

    test.beforeEach(async ({ page }, testInfo) => {
        const response = await page.goto(`${PATH}?v=${Date.now()}`);

        expect(response.status()).toBe(200);
        // A successful control proves we reached this fixture in the actual mode under test.
        expect(response.headers()['x-magewire-playwright-mode']).toBe(testInfo.config.metadata.magentoMode);
        await expect(page.getByTestId('fixture-count')).toHaveText('0');
    });

    test('renders a queued complex message and still commits component updates', async ({ page }, testInfo) => {
        await queueComplexMessage(page);
        const response = await page.goto(`${PATH}?v=${Date.now()}`);

        expect(response.status()).toBe(200);
        expect(response.headers()['x-magewire-playwright-mode']).toBe(testInfo.config.metadata.magentoMode);
        await expect(page.getByTestId('fixture-complex-message')).toHaveText(complexMessage);

        const [update] = await Promise.all([
            page.waitForResponse(response => response.url().includes('/magewire/update')),
            page.getByTestId('fixture-increment').click(),
        ]);

        expect(update.status()).toBe(200);
        await expect(page.getByTestId('fixture-count')).toHaveText('1');
    });

    for (const kind of ['exception', 'type-error']) {
        test(`${kind} during page rendering exposes diagnostics only in developer mode`, async ({ page }, testInfo) => {
            const response = await page.goto(`${PATH}?failure=${kind}&v=${Date.now()}`);

            expect(response.status()).toBe(500);
            assertDiagnostics(await response.text(), testInfo.config.metadata.magentoMode);
            assertDiagnostics(await page.locator('body').innerText(), testInfo.config.metadata.magentoMode);
        });

        test(`${kind} during an update exposes diagnostics only in developer mode`, async ({ page }, testInfo) => {
            const [response] = await Promise.all([
                page.waitForResponse(response => response.url().includes('/magewire/update')),
                page.getByTestId(`fixture-${kind}`).click(),
            ]);

            expect(response.status()).toBe(500);
            expect(response.headers()).not.toHaveProperty('x-magewire-message-severity');
            assertDiagnostics(await response.text(), testInfo.config.metadata.magentoMode);

            // Magewire presents unexpected errors in an iframe; inspecting the parent DOM misses it.
            const presented = page.frameLocator('#livewire-error iframe').locator('body');
            await expect(presented).toContainText(testInfo.config.metadata.magentoMode === 'developer'
                ? PRIVATE_MARKER : GENERIC_ERROR);
            assertDiagnostics(await presented.innerText(), testInfo.config.metadata.magentoMode);
            await expect(page.getByTestId('fixture-count')).toHaveText('0');
        });
    }
});
