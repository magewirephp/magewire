import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/addons';
const notifierFixture = page => page.getByTestId('addons-notifier');
const notifications = page => page.locator('.magewire-notifier-message');
const notificationOfType = (page, type) => page.locator(`.magewire-notifier-item[data-type="${type}"]`);
const occurrenceBadge = page => page.locator('.magewire-notifier-occurrences');
const occurrenceBadgeOfType = (page, type) => page.locator(`.message.${type} .magewire-notifier-occurrences`);
const occurrenceBadgeValue = page => occurrenceBadge(page).locator('[aria-hidden="true"]');
const visibleOccurrenceBadges = page => page.locator('.magewire-notifier-occurrences:visible');

async function createNotification(page, message, type = 'warning') {
    const fixture = notifierFixture(page);
    const submissions = fixture.getByTestId('addons-notifier-submissions');
    const submission = Number(await submissions.textContent()) + 1;

    await fixture.getByTestId('addons-notifier-message').fill(message);
    await fixture.getByTestId('addons-notifier-type').selectOption(type);
    await fixture.getByTestId('addons-notifier-create').click();
    await expect(submissions).toHaveText(String(submission));

    return {
        id: Number(await fixture.getByTestId('addons-notifier-id').textContent()),
        occurrences: Number(await fixture.getByTestId('addons-notifier-occurrences').textContent()),
        total: Number(await fixture.getByTestId('addons-notifier-total').textContent()),
    };
}

test.describe('Magewire Playwright — Notifier', () => {
    test.beforeEach(async ({ page }) => {
        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);
        await page.waitForFunction(() => window.MagewireAddons?.notifier);
        await expect(notifierFixture(page).getByRole('heading')).toHaveText('Notifier addon');
    });

    test('loads the framework-independent presentation from Magewire core', async ({ page }) => {
        await createNotification(page, 'Styled without a theme build.', 'info');

        const stylesheetLoaded = await page.evaluate(() => Array.from(document.styleSheets).some(
            stylesheet => stylesheet.href?.includes('Magewirephp_Magewire/css/magewire.css'),
        ));

        expect(stylesheetLoaded).toBe(true);

        await page.evaluate(() => {
            document.querySelectorAll('link[rel~="stylesheet"]').forEach(stylesheet => {
                if (!stylesheet.href.includes('Magewirephp_Magewire/css/magewire.css')) {
                    stylesheet.disabled = true;
                }
            });

            document.querySelectorAll('style').forEach(stylesheet => {
                stylesheet.disabled = true;
            });

            const probe = document.createElement('div');
            probe.innerHTML = [
                '<span data-magewire-css-probe="loading" wire:loading></span>',
                '<span data-magewire-css-probe="dirty" wire:dirty></span>',
                '<span data-magewire-css-probe="cloak" x-cloak></span>',
            ].join('');
            document.body.append(probe);
        });

        await expect(page.locator('.magewire-notifier')).toHaveCSS('position', 'fixed');

        const notification = notificationOfType(page, 'info');
        await expect(notification).toHaveClass(/\bmessage\b/);
        await expect(notification).not.toHaveClass(/\btoast\b/);
        await expect(notification).toHaveCSS('display', 'flex');
        await expect(notification).toHaveCSS('position', 'relative');
        await expect(notification).toHaveCSS('border-top-width', '1px');
        await expect(notification).toHaveCSS('border-inline-start-width', '4px');
        await expect(notification).toHaveCSS('border-radius', '12px');
        await expect(notification).toHaveCSS('font-size', '14px');
        await expect(page.locator('[data-magewire-css-probe="loading"]')).toBeHidden();
        await expect(page.locator('[data-magewire-css-probe="dirty"]')).toBeHidden();
        await expect(page.locator('[data-magewire-css-probe="cloak"]')).toBeHidden();
    });

    test('updates the previous active notification when its message and type are equal', async ({ page }) => {
        const first = await createNotification(page, 'Too many requests! Please wait.');

        await expect(notifications(page)).toHaveCount(1);
        await expect(visibleOccurrenceBadges(page)).toHaveCount(0);

        const second = await createNotification(page, 'Too many requests! Please wait.');

        expect(second.id).toBe(first.id);
        expect(second.total).toBe(1);
        expect(second.occurrences).toBe(2);

        await expect(occurrenceBadgeValue(page)).toHaveText('2');
        await expect(occurrenceBadge(page)).toBeVisible();
    });

    test('keeps a two-digit occurrence count readable in a content-sized badge', async ({ page }) => {
        let notification;

        for (let occurrence = 1; occurrence <= 10; occurrence += 1) {
            notification = await createNotification(page, 'Still too many requests.');
        }

        expect(notification.total).toBe(1);
        expect(notification.occurrences).toBe(10);

        await expect(occurrenceBadgeValue(page)).toHaveText('10');
        await expect(occurrenceBadge(page)).toBeVisible();
        await expect(occurrenceBadge(page)).toHaveCSS('position', 'absolute');

        const dimensions = await occurrenceBadge(page).evaluate(element => ({
            height: element.offsetHeight,
            width: element.offsetWidth,
        }));

        expect(dimensions.width).toBeGreaterThan(dimensions.height);
    });

    test('starts bouncing only after the occurrence count passes ten', async ({ page }) => {
        let notification;

        for (let occurrence = 1; occurrence <= 10; occurrence += 1) {
            notification = await createNotification(page, 'Keep warning me.');
        }

        expect(notification.occurrences).toBe(10);
        await expect(occurrenceBadge(page)).not.toHaveClass(/\bmagewire-notifier-occurrences--emphasized\b/);

        notification = await createNotification(page, 'Keep warning me.');

        expect(notification.occurrences).toBe(11);
        await expect(occurrenceBadge(page)).toHaveClass(/\bmagewire-notifier-occurrences--emphasized\b/);
        await expect(occurrenceBadge(page)).toHaveCSS('animation-name', 'magewire-bounce');
    });

    test('exposes the notification type as the badge styling hook', async ({ page }) => {
        const types = ['success', 'info', 'warning', 'error'];

        for (const type of types) {
            await createNotification(page, `${type} notification.`, type);
            await createNotification(page, `${type} notification.`, type);
        }

        for (const type of types) {
            await expect(occurrenceBadgeOfType(page, type)).toBeVisible();
            await expect(notificationOfType(page, type)).toHaveClass(new RegExp(`\\b${type}\\b`));
        }
    });

    test('keeps equal text with a different type as a separate notification', async ({ page }) => {
        await createNotification(page, 'Connection changed.', 'warning');
        const second = await createNotification(page, 'Connection changed.', 'error');

        expect(second.total).toBe(2);
        expect(second.occurrences).toBe(1);

        await expect(notifications(page)).toHaveCount(2);
        await expect(visibleOccurrenceBadges(page)).toHaveCount(0);
    });

    test('keeps different text with the same type as a separate notification', async ({ page }) => {
        await createNotification(page, 'First warning.');
        const second = await createNotification(page, 'Second warning.');

        expect(second.total).toBe(2);
        expect(second.occurrences).toBe(1);

        await expect(notifications(page)).toHaveCount(2);
        await expect(visibleOccurrenceBadges(page)).toHaveCount(0);
    });

    test('preserves notification state-change hooks', async ({ page }) => {
        const states = await page.evaluate(async () => {
            const states = [];

            await window.MagewireAddons.notifier.create(
                'Track my lifecycle.',
                { duration: false },
                { onStateChange: ({ state }) => states.push(state) }
            );

            return states;
        });

        expect(states).toEqual(['running', 'succeeded']);
    });
});
