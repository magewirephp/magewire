import { test, expect } from '@playwright/test';

/**
 * Request filters reject an update before any component is reconstructed. The rejection travels
 * back as a plain HTTP response: the body carries the message, and an X-Magewire-Message-Severity
 * header both marks that body as written for the customer and says how much it weighs.
 *
 * These specs pin the frontend half of that contract. The update round-trip is stubbed rather than
 * provoked through a real filter, because the shipped filter (rate limiting) is configuration
 * driven and global, so tripping it for real would rate limit every other spec sharing the store.
 * Stubbing keeps each case deterministic and lets a single page cover severities and statuses that
 * no shipped filter produces yet.
 */

const PATH = '/magewire/playwright/lazyloading';
const UPDATE_ROUTE = '**/magewire/update**';
const SEVERITY_HEADER = 'X-Magewire-Message-Severity';

const TESTID = {
    increment: 'lazy-host-increment',
    count: 'lazy-host-count',
};

const testid = (page, id) => page.locator(`[data-testid="${id}"]`);

/**
 * The notifier renders one element per notification, classed `message` plus the severity it was
 * handed. Scoping to the message paragraph keeps the assertion on what the customer actually reads.
 */
const notifications = page => page.locator('.magewire-notifier-message');
const notificationOf = (page, severity) => page.locator(`.message.${severity} .magewire-notifier-message`);

/**
 * Stub every subsequent update round-trip with a crafted failure.
 *
 * Registered only after the page has settled, so the lazy children on this page complete their own
 * real commits first and the stub applies to the interaction under test alone.
 */
async function stubUpdateFailure(page, { status, body, severity = null }) {
    await page.route(UPDATE_ROUTE, route => route.fulfill({
        status,
        headers: severity === null ? {} : { [SEVERITY_HEADER]: severity },
        contentType: 'text/plain',
        body,
    }));
}

test.describe('Magewire Playwright — Request Filters', () => {
    test.beforeEach(async ({ page }) => {
        /*
         * A missing notifier addon falls back to alert(), which would hang the run. Dismissing any
         * dialog turns that failure mode into a plain assertion failure instead of a timeout.
         */
        page.on('dialog', dialog => dialog.dismiss().catch(() => {}));

        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);

        await expect(testid(page, TESTID.count)).toHaveText('0');
    });

    /**
     * Control: without a stub the same interaction commits normally. Without this, a spec asserting
     * "the count did not move" would also pass if the button were simply broken.
     */
    test('commits normally when nothing rejects the request', async ({ page }) => {
        await testid(page, TESTID.increment).click();

        await expect(testid(page, TESTID.count)).toHaveText('1');
        await expect(notifications(page)).toHaveCount(0);
    });

    test('presents the response body when the severity header marks it', async ({ page }) => {
        await stubUpdateFailure(page, {
            status: 429,
            severity: 'warning',
            body: 'Too many requests! Please wait.',
        });

        await testid(page, TESTID.increment).click();

        await expect(notifications(page)).toHaveText('Too many requests! Please wait.');
    });

    test('leaves the component untouched when a request is rejected', async ({ page }) => {
        await stubUpdateFailure(page, {
            status: 429,
            severity: 'warning',
            body: 'Too many requests! Please wait.',
        });

        await testid(page, TESTID.increment).click();

        await expect(notifications(page)).toHaveCount(1);
        await expect(testid(page, TESTID.count)).toHaveText('0');
    });

    test('applies the severity the header carries', async ({ page }) => {
        await stubUpdateFailure(page, {
            status: 403,
            severity: 'error',
            body: 'Verification failed.',
        });

        await testid(page, TESTID.increment).click();

        await expect(notificationOf(page, 'error')).toHaveText('Verification failed.');
    });

    /**
     * Status is deliberately not consulted: a filter picks whatever status its rejection deserves,
     * and the header alone decides whether the body reaches the customer.
     */
    test('presents a marked rejection regardless of its status class', async ({ page }) => {
        await stubUpdateFailure(page, {
            status: 503,
            severity: 'info',
            body: 'Checkout is briefly unavailable.',
        });

        await testid(page, TESTID.increment).click();

        await expect(notificationOf(page, 'info')).toHaveText('Checkout is briefly unavailable.');
    });

    /**
     * The counterpart guarantee: an unmarked failure is left to Magewire's regular failure
     * handling, so a stack trace or an error page can never reach a customer.
     */
    test('ignores a failure that carries no severity header', async ({ page }) => {
        await stubUpdateFailure(page, {
            status: 429,
            body: 'Too Many Requests.',
        });

        await testid(page, TESTID.increment).click();

        await expect(notifications(page)).toHaveCount(0);
    });

    test('ignores an unmarked server error rather than surfacing its body', async ({ page }) => {
        await stubUpdateFailure(page, {
            status: 500,
            body: 'SQLSTATE[42S02]: Base table or view not found',
        });

        await testid(page, TESTID.increment).click();

        await expect(notifications(page)).toHaveCount(0);
    });

    test('ignores a marked rejection that carries no message', async ({ page }) => {
        await stubUpdateFailure(page, {
            status: 429,
            severity: 'warning',
            body: '   ',
        });

        await testid(page, TESTID.increment).click();

        await expect(notifications(page)).toHaveCount(0);
    });
});
