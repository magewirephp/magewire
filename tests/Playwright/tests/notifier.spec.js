import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/addons';
const notifierFixture = page => page.getByTestId('addons-notifier');
const notifications = page => page.locator('.magewire-notifier-message');
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
        await expect(occurrenceBadge(page)).not.toHaveClass(/\banimate-bounce\b/);

        notification = await createNotification(page, 'Keep warning me.');

        expect(notification.occurrences).toBe(11);
        await expect(occurrenceBadge(page)).toHaveClass(/\banimate-bounce\b/);
    });

    test('exposes the notification type as the badge styling hook', async ({ page }) => {
        const types = ['success', 'info', 'warning', 'error'];

        for (const type of types) {
            await createNotification(page, `${type} notification.`, type);
            await createNotification(page, `${type} notification.`, type);
        }

        for (const type of types) {
            await expect(occurrenceBadgeOfType(page, type)).toBeVisible();
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
