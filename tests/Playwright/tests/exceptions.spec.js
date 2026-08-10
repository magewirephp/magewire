import { test, expect } from '@playwright/test';

/**
 * End-to-end cover for the request filter pipeline, driving the real server path: filter, exception,
 * handler, response and frontend presentation. Where request-filters.spec.js stubs the round-trip to
 * pin the browser contract, this one lets Magento answer for itself.
 *
 * Rejections come from a test-only filter registered on this page alone, which decides purely from
 * the pending method call in the parsed envelope. That it can decide at all is the point: a filter
 * runs before the component is reconstructed.
 */

const PATH = '/magewire/playwright/exceptions';
const UPDATE_URL = '/magewire/update';
const SEVERITY_HEADER = 'x-magewire-message-severity';

const TESTID = {
    count: 'exceptions-count',
    increment: 'exceptions-increment',
    component: 'exceptions-reject-component',
};

const testid = (page, id) => page.locator(`[data-testid="${id}"]`);
const notifications = page => page.locator('.magewire-notifier-message');
const notificationOf = (page, severity) => page.locator(`.message.${severity} .magewire-notifier-message`);

/**
 * Click a button and hand back the update response it produced.
 */
async function clickAndCaptureUpdate(page, id) {
    const [response] = await Promise.all([
        page.waitForResponse(response => response.url().includes(UPDATE_URL)),
        testid(page, id).click(),
    ]);

    return response;
}

/**
 * Every rejection the Playwright filter can raise, mirroring PlaywrightRejectionFilter::REJECTIONS.
 * Each status sits outside the 2xx range on purpose, success included: the frontend hook only sees
 * responses the browser treats as failures.
 */
const REJECTIONS = [
    { name: 'warning', testid: 'exceptions-reject-warning', status: 429 },
    { name: 'error', testid: 'exceptions-reject-error', status: 403 },
    { name: 'info', testid: 'exceptions-reject-info', status: 503 },
    { name: 'success', testid: 'exceptions-reject-success', status: 409 },
];

test.describe('Magewire Playwright — Exceptions', () => {
    test.beforeEach(async ({ page }) => {
        page.on('dialog', dialog => dialog.dismiss().catch(() => {}));

        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);

        await expect(testid(page, TESTID.count)).toHaveText('0');
    });

    test('renders the page with the correct title', async ({ page }) => {
        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Exceptions');
    });

    /**
     * Control: the same component commits normally when nothing rejects it. Without this, every
     * "the counter did not move" assertion below would also pass on a broken button.
     */
    test('commits a call that no filter rejects', async ({ page }) => {
        const response = await clickAndCaptureUpdate(page, TESTID.increment);

        expect(response.status()).toBe(200);
        expect(response.headers()).not.toHaveProperty(SEVERITY_HEADER);

        await expect(testid(page, TESTID.count)).toHaveText('1');
        await expect(notifications(page)).toHaveCount(0);
    });

    for (const rejection of REJECTIONS) {
        test.describe(`rejected with ${rejection.name} severity`, () => {
            test(`answers ${rejection.status} carrying the message and severity`, async ({ page }) => {
                const response = await clickAndCaptureUpdate(page, rejection.testid);

                expect(response.status()).toBe(rejection.status);
                expect(response.headers()[SEVERITY_HEADER]).toBe(rejection.name);
                expect(await response.text())
                    .toBe(`Rejected by the Playwright filter with a ${rejection.name} severity.`);
            });

            test('presents the message at the severity the header carries', async ({ page }) => {
                await testid(page, rejection.testid).click();

                await expect(notificationOf(page, rejection.name))
                    .toHaveText(`Rejected by the Playwright filter with a ${rejection.name} severity.`);
            });

            /**
             * The component method behind each of these buttons increments the counter. A counter
             * that never moves is what proves the filter rejected before reconstruction, rather
             * than somewhere further down the lifecycle.
             */
            test('never reaches the component method', async ({ page }) => {
                await testid(page, rejection.testid).click();

                await expect(notifications(page)).toHaveCount(1);
                await expect(testid(page, TESTID.count)).toHaveText('0');
            });
        });
    }

    /**
     * A rejection raised inside the component, after reconstruction, rather than by a filter. Same
     * exception base, so the same handler answers it and the frontend cannot tell the difference.
     */
    test.describe('rejected from within the component', () => {
        test('answers 418 carrying the message and severity', async ({ page }) => {
            const response = await clickAndCaptureUpdate(page, TESTID.component);

            expect(response.status()).toBe(418);
            expect(response.headers()[SEVERITY_HEADER]).toBe('error');
            expect(await response.text()).toBe('Rejected from within the component.');
        });

        test('presents the message like any other rejection', async ({ page }) => {
            await testid(page, TESTID.component).click();

            await expect(notificationOf(page, 'error'))
                .toHaveText('Rejected from within the component.');
        });

        /**
         * The method does increment before it throws, but the response never commits, so the
         * rendered counter stays put.
         */
        test('leaves the rendered counter untouched', async ({ page }) => {
            await testid(page, TESTID.component).click();

            await expect(notifications(page)).toHaveCount(1);
            await expect(testid(page, TESTID.count)).toHaveText('0');
        });
    });
});
