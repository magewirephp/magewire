import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/emitup';
const NAMES = ['grandparent', 'parent', 'child', 'sibling', 'stranger'];

const node = (page, name) => page.getByTestId(`magewire.playwright.emit-up.${name}`);
const own = (page, name, testId) => node(page, name).locator(`:scope > [data-testid="${testId}"]`);
const received = async page => Object.fromEntries(
    await Promise.all(NAMES.map(async name => [name, await own(page, name, 'received').textContent()]))
);

test.describe('Magewire Playwright — Backwards Compatibility — Emit Up', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${PATH}?v=${Math.floor(Math.random() * 1_000_000)}`);
        await page.waitForFunction(names => names.every(
            name => window.Magewire?.find?.(`magewire.playwright.emit-up.${name}`)
        ), NAMES);
    });

    test('emitUp() reaches the parent and the grandparent only, as in Magewire 1', async ({ page }) => {
        await own(page, 'child', 'pick').click();

        await expect(own(page, 'grandparent', 'received')).toHaveText('1');
        await page.waitForLoadState('networkidle');
        expect(await received(page)).toEqual({ grandparent: '1', parent: '1', child: '0', sibling: '0', stranger: '0' });
    });

    test('emitUp() of a component without ancestors reaches nobody and fails nothing', async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));

        await own(page, 'stranger', 'pick').click();
        await page.waitForLoadState('networkidle');

        expect(errors).toEqual([]);
        expect(await received(page)).toEqual({ grandparent: '0', parent: '0', child: '0', sibling: '0', stranger: '0' });
    });

    test('emitUp()->self() reaches the emitter only, as in Magewire 1', async ({ page }) => {
        await own(page, 'child', 'pick-self').click();

        await expect(own(page, 'child', 'received')).toHaveText('1');
        await page.waitForLoadState('networkidle');
        expect(await received(page)).toEqual({ grandparent: '0', parent: '0', child: '1', sibling: '0', stranger: '0' });
    });

    test('emitUp()->to() reaches the named component only, as in Magewire 1', async ({ page }) => {
        await own(page, 'child', 'pick-stranger').click();

        await expect(own(page, 'stranger', 'received')).toHaveText('1');
        await page.waitForLoadState('networkidle');
        expect(await received(page)).toEqual({ grandparent: '0', parent: '0', child: '0', sibling: '0', stranger: '1' });
    });

    test('emitToRefreshUp() re-renders the ancestors only, without a $refresh listener', async ({ page }) => {
        const before = Object.fromEntries(
            await Promise.all(NAMES.map(async name => [name, await own(page, name, 'rendered').textContent()]))
        );

        await own(page, 'child', 'refresh-up').click();

        await expect(own(page, 'parent', 'rendered')).not.toHaveText(before.parent);
        await expect(own(page, 'grandparent', 'rendered')).not.toHaveText(before.grandparent);
        await page.waitForLoadState('networkidle');
        await expect(own(page, 'sibling', 'rendered')).toHaveText(before.sibling);
        await expect(own(page, 'stranger', 'rendered')).toHaveText(before.stranger);
    });

    test('dispatch() keeps the Livewire 3 delivery to every listener on a backwards compatible component', async ({ page }) => {
        await own(page, 'child', 'broadcast').click();

        for (const name of NAMES) {
            await expect(own(page, name, 'received')).toHaveText('1');
        }
    });
});
