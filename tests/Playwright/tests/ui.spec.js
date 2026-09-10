import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/ui';
const workbench = page => page.getByTestId('ui-workbench');
const visibleNotifications = page => page.locator('.magewire-notifier-item:visible');
const notificationOfType = (page, type) => page.locator(`.magewire-notifier-item[data-type="${type}"]:visible`);

async function visitWorkbench(page, query = '') {
    const version = Math.floor(Math.random() * 1_000_000);
    await page.goto(`${PATH}?v=${version}${query}`);
    await page.waitForFunction(() => window.MagewireAddons?.notifier);
    await expect(workbench(page)).toBeVisible();
    await expect(visibleNotifications(page)).toHaveCount(4);
}

test.describe('Magewire Playwright — UI workbench', () => {
    test('renders every core visual primitive in a persistent preview', async ({ page }) => {
        await visitWorkbench(page);

        await expect(workbench(page).getByRole('heading', { level: 1 })).toHaveText('Magewire UI');
        await expect(workbench(page).getByTestId('ui-notifications-persistent')).toBeChecked();
        await expect(workbench(page).getByTestId('ui-notification-mode'))
            .toHaveText('Notifications remain visible until dismissed.');

        for (const type of ['success', 'info', 'warning', 'error']) {
            await expect(notificationOfType(page, type)).toHaveCount(1);
        }

        await expect(notificationOfType(page, 'info').locator('.magewire-notifier-occurrences [aria-hidden="true"]'))
            .toHaveText('2');
        await expect(notificationOfType(page, 'info').locator('.magewire-notifier-activity-state'))
            .toBeVisible();
        await expect(notificationOfType(page, 'warning').locator('.magewire-notifier-occurrences [aria-hidden="true"]'))
            .toHaveText('11');
        await expect(notificationOfType(page, 'warning').locator('.magewire-notifier-occurrences'))
            .toHaveClass(/\bmagewire-notifier-occurrences--emphasized\b/);

        await expect(workbench(page).getByTestId('ui-loading-icon').locator('.magewire-loading-icon'))
            .toBeVisible();
        await expect(workbench(page).getByTestId('ui-exception').locator('.magewire-exception'))
            .toBeVisible();
        await expect(workbench(page).getByTestId('ui-pagination')).toBeVisible();
        await expect(workbench(page).getByTestId('ui-pagination-items').getByRole('listitem'))
            .toHaveCount(3);
    });

    test('can preview expiring notifications and clear the collection', async ({ page }) => {
        await visitWorkbench(page);

        await workbench(page).getByTestId('ui-notifications-persistent').uncheck();
        await workbench(page).getByTestId('ui-notifications-duration').fill('250');
        await workbench(page).getByTestId('ui-notifications-show').click();

        await expect(workbench(page).getByTestId('ui-notification-mode'))
            .toHaveText('Notifications close after 250 ms.');
        await expect(visibleNotifications(page)).toHaveCount(4);
        await expect(visibleNotifications(page)).toHaveCount(0, { timeout: 3000 });

        await workbench(page).getByTestId('ui-notifications-persistent').check();
        await workbench(page).getByTestId('ui-notifications-show').click();
        await expect(visibleNotifications(page)).toHaveCount(4);

        await workbench(page).getByTestId('ui-notifications-clear').click();
        await expect(page.locator('.magewire-notifier-item')).toHaveCount(0);
        await expect(workbench(page).getByTestId('ui-notification-count')).toHaveText('0');
    });

    test('exercises dirty, offline, and loading states', async ({ page, context }) => {
        await visitWorkbench(page);

        const dirty = workbench(page).getByTestId('ui-dirty-state');
        const offline = workbench(page).getByTestId('ui-offline-state');
        const loading = workbench(page).getByTestId('ui-request-loading');

        await expect(dirty).toBeHidden();
        await workbench(page).getByTestId('ui-dirty-input').fill('Unsaved value');
        await expect(dirty).toBeVisible();

        await expect(offline).toBeHidden();
        await context.setOffline(true);
        await expect(offline).toBeVisible();
        await context.setOffline(false);
        await expect(offline).toBeHidden();

        const responsePromise = page.waitForResponse(response => (
            response.request().method() === 'POST'
            && response.url().includes('/magewire/update')
        ));

        await workbench(page).getByTestId('ui-request-start').click();
        await expect(loading).toBeVisible();

        const response = await responsePromise;
        expect(response.ok()).toBe(true);
        await expect(workbench(page).getByTestId('ui-request-count')).toHaveText('1');
        await expect(loading).toBeHidden();
        await expect(dirty).toBeHidden();
    });

    test('exercises numbered, previous, and next pagination states', async ({ page }) => {
        await visitWorkbench(page);

        const pagination = workbench(page).getByTestId('ui-pagination');
        const items = workbench(page).getByTestId('ui-pagination-items');

        await expect(workbench(page).getByTestId('ui-pagination-current-page')).toHaveText('1 / 3');
        await expect(pagination.getByTestId('ui-pagination-previous')).toBeDisabled();
        await expect(pagination.getByTestId('ui-pagination-page-1')).toHaveAttribute('aria-current', 'page');
        await expect(items).toContainText('Reactive storefront components');

        await pagination.getByTestId('ui-pagination-next').click();
        await expect(workbench(page).getByTestId('ui-pagination-current-page')).toHaveText('2 / 3');
        await expect(pagination.getByTestId('ui-pagination-page-2')).toHaveAttribute('aria-current', 'page');
        await expect(items).toContainText('Loading and activity states');

        await pagination.getByTestId('ui-pagination-page-3').click();
        await expect(workbench(page).getByTestId('ui-pagination-current-page')).toHaveText('3 / 3');
        await expect(pagination.getByTestId('ui-pagination-next')).toBeDisabled();
        await expect(items).toContainText('Accessible interaction states');

        await pagination.getByTestId('ui-pagination-previous').click();
        await expect(workbench(page).getByTestId('ui-pagination-current-page')).toHaveText('2 / 3');
    });
});
