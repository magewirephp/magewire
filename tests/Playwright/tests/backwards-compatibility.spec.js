import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/backwardscompatibility';
const ID = 'magewire.playwright.backwards_compatibility.browser_events';

/**
 * Calls a component method and resolves with the event.detail a window listener receives.
 */
async function detailOf(page, event, method, payload) {
    return page.evaluate(({ id, event, method, payload }) => new Promise(resolve => {
        window.addEventListener(event, e => resolve(e.detail), { once: true });
        window.Magewire.find(id).call(method, payload);
    }), { id: ID, event, method, payload });
}

test.describe('Magewire Playwright — Backwards Compatibility', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${PATH}?v=${Math.floor(Math.random() * 1_000_000)}`);
        await page.waitForFunction(id => window.Magewire?.find?.(id), ID);
    });

    for (const { payload, detail } of [
        { payload: 'empty', detail: {} },
        { payload: 'array', detail: { id: 5 } },
        { payload: 'object', detail: { id: 5 } },
        { payload: 'string', detail: 'saved' },
    ]) {
        test(`dispatchBrowserEvent() delivers a Magewire 1 "${payload}" payload as event.detail`, async ({ page }) => {
            expect(await detailOf(page, 'playwright:legacy', 'legacy', payload)).toEqual(detail);
        });
    }

    for (const { payload, detail } of [
        { payload: 'empty', detail: [] },
        { payload: 'named', detail: { id: 5 } },
        { payload: 'string', detail: ['saved'] },
    ]) {
        test(`dispatch() keeps the Livewire 3 "${payload}" event.detail on a backwards compatible component`, async ({ page }) => {
            expect(await detailOf(page, 'playwright:modern', 'modern', payload)).toEqual(detail);
        });
    }

    test('dispatchBrowserEvent() fires on the component root and bubbles', async ({ page }) => {
        const target = await page.evaluate(id => new Promise(resolve => {
            window.addEventListener('playwright:legacy', e => resolve(e.target.getAttribute('wire:id')), { once: true });
            window.Magewire.find(id).call('legacy', 'string');
        }), ID);

        expect(target).toBe(ID);
    });
});
