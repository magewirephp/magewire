import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/flakes';

test.describe('Magewire Playwright — Flakes', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(PATH);
    });

    test('renders nested, slotted, and repeated Flakes', async ({ page }) => {
        await expect(page).toHaveTitle('Magewire / Playwright / Flakes');
        await expect(page.getByTestId('flake-gallery')).toBeVisible();

        const profileCard = page.getByTestId('profile-card');
        await expect(profileCard.locator('.mw-flake-heading')).toHaveText('User profile');
        await expect(profileCard.locator('.mw-flake-badge')).toHaveCount(2);
        await expect(profileCard.locator('.mw-flake-card__footer .mw-flake-button')).toHaveCount(2);

        const slotCard = page.getByTestId('slot-card');
        await expect(slotCard.locator('.mw-flake-card__header')).toContainText('Named slot composition');
        await expect(slotCard.locator('.mw-flake-card__footer')).toContainText('View structure');

        const repeated = page.getByTestId('repeated-flakes').locator('.mw-flake-card');
        await expect(repeated).toHaveCount(3);
        await expect(repeated.nth(0)).toHaveAttribute('data-variant', 'default');
        await expect(repeated.nth(1)).toHaveAttribute('data-variant', 'muted');
        await expect(repeated.nth(2)).toHaveAttribute('data-variant', 'accent');

        const occurrenceIds = await page
            .getByTestId('flake-gallery')
            .locator('[wire\\:id]')
            .evaluateAll((elements) => elements.map((element) => element.getAttribute('wire:id')));

        expect(occurrenceIds).toHaveLength(1);
        expect(new Set(occurrenceIds).size).toBe(occurrenceIds.length);
    });

    test('keeps presentation Flakes stateless and supports an explicitly reactive Flake', async ({ page }) => {
        const profileCard = page.getByTestId('profile-card');
        await expect(profileCard).not.toHaveAttribute('wire:id', /.+/);
        await expect(profileCard.locator('[wire\\:id]')).toHaveCount(0);

        const counter = page.getByTestId('reactive-flake-counter');
        const count = page.getByTestId('reactive-flake-count');
        await expect(counter).toHaveAttribute('wire:id', /.+/);
        const initialCounterId = await counter.getAttribute('wire:id');
        await expect(count).toHaveText('0');

        await counter.getByRole('button', { name: 'Increment reactive Flake' }).click();

        await expect(count).toHaveText('1');
        await expect(counter).toHaveAttribute('wire:id', initialCounterId);

        await page.getByTestId('flake-gallery-refresh').click();

        await expect(page.getByTestId('flake-gallery-refresh-count')).toHaveText('1');
        await expect(count).toHaveText('1');
        await expect(profileCard.locator('.mw-flake-heading')).toHaveText('User profile');
    });

    test('re-renders the Magewire host without losing Flake composition', async ({ page }) => {
        const count = page.getByTestId('flake-gallery-refresh-count');
        await expect(count).toHaveText('0');

        await page.getByTestId('flake-gallery-refresh').click();

        await expect(count).toHaveText('1');
        await expect(page.getByTestId('profile-card').locator('.mw-flake-heading')).toHaveText('User profile');
        await expect(page.getByTestId('repeated-flakes').locator('.mw-flake-card')).toHaveCount(3);
    });
});
