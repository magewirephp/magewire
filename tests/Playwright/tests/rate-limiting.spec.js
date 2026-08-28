import { test, expect } from '@playwright/test';

/**
 * End-to-end coverage for the real request limiter and lockout path. A developer-only filter
 * scopes the fixed test policy to this fixture's component snapshot, so these requests cannot
 * consume the budgets of other Playwright specs running against the same Magento store.
 */

const PATH = '/magewire/playwright/ratelimiting';
const UPDATE_URL = '/magewire/update';
const SEVERITY_HEADER = 'x-magewire-message-severity';
const RETRY_AFTER_HEADER = 'retry-after';
const REGULAR_WARNING = 'Too many requests! Please wait.';

const TESTID = {
    count: 'rate-limiting-count',
    increment: 'rate-limiting-increment',
};

const testid = (page, id) => page.locator(`[data-testid="${id}"]`);
const notificationOf = (page, severity) => page.locator(`.message.${severity} .magewire-notifier-message`);

async function clickAndCaptureUpdate(page) {
    const [response] = await Promise.all([
        page.waitForResponse(response => response.url().includes(UPDATE_URL)),
        testid(page, TESTID.increment).click(),
    ]);

    return response;
}

async function clickAndAssertNoUpdate(page) {
    let requests = 0;
    const countRequest = request => {
        if (request.url().includes(UPDATE_URL)) {
            requests++;
        }
    };

    page.on('request', countRequest);

    try {
        await testid(page, TESTID.increment).click();
        await page.waitForTimeout(250);
    } finally {
        page.off('request', countRequest);
    }

    expect(requests).toBe(0);
}

async function consumeBudget(page) {
    for (const count of ['1', '2']) {
        const response = await clickAndCaptureUpdate(page);

        expect(response.status()).toBe(200);
        await expect(testid(page, TESTID.count)).toHaveText(count);
    }
}

async function rejectAtLimit(page) {
    const response = await clickAndCaptureUpdate(page);

    expect(response.status()).toBe(429);
    expect(response.headers()[SEVERITY_HEADER]).toBe('warning');
    expect(response.headers()).not.toHaveProperty(RETRY_AFTER_HEADER);
    expect(await response.text()).toBe(REGULAR_WARNING);
    await expect(testid(page, TESTID.count)).toHaveText('2');

    return response;
}

async function triggerLockout(page) {
    const response = await clickAndCaptureUpdate(page);

    expect(response.status()).toBe(429);
    expect(response.headers()[SEVERITY_HEADER]).toBe('warning');
    expect(response.headers()[RETRY_AFTER_HEADER]).toBe('6');
    expect(await response.text()).toBe('You have been temporarily locked out due to too many requests. Try again in 6 seconds.');
    await expect(testid(page, TESTID.count)).toHaveText('2');

    return response;
}

test.describe('Magewire Playwright — Rate Limiting', () => {
    test.beforeEach(async ({ page }) => {
        page.on('dialog', dialog => dialog.dismiss().catch(() => {}));

        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);

        await expect(testid(page, TESTID.count)).toHaveText('0');
    });

    test('allows the configured request budget before returning 429', async ({ page }) => {
        await consumeBudget(page);
        await rejectAtLimit(page);

        await expect(notificationOf(page, 'warning')).toHaveText(REGULAR_WARNING);
    });

    test('prevents another browser request while the lockout is active, including after reload', async ({ page }) => {
        await consumeBudget(page);
        await rejectAtLimit(page);
        await triggerLockout(page);

        await clickAndAssertNoUpdate(page);
        await expect(testid(page, TESTID.count)).toHaveText('2');

        await page.reload();
        await expect(testid(page, TESTID.count)).toHaveText('0');
        await clickAndAssertNoUpdate(page);
        await expect(testid(page, TESTID.count)).toHaveText('0');
    });

    test('accepts requests again after the lockout expires', async ({ page }) => {
        test.setTimeout(45_000);

        await consumeBudget(page);
        await rejectAtLimit(page);
        await triggerLockout(page);

        await page.waitForTimeout(6_200);

        const response = await clickAndCaptureUpdate(page);

        expect(response.status()).toBe(200);
        await expect(testid(page, TESTID.count)).toHaveText('3');
    });

    test('does not lock out a different browser session', async ({ page, browser, baseURL }) => {
        await consumeBudget(page);
        await rejectAtLimit(page);
        await triggerLockout(page);

        const otherContext = await browser.newContext({ baseURL, ignoreHTTPSErrors: true });

        try {
            const otherPage = await otherContext.newPage();
            await otherPage.goto(PATH);
            await expect(testid(otherPage, TESTID.count)).toHaveText('0');

            const response = await clickAndCaptureUpdate(otherPage);

            expect(response.status()).toBe(200);
            await expect(testid(otherPage, TESTID.count)).toHaveText('1');
        } finally {
            await otherContext.close();
        }
    });
});
