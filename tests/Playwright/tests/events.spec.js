import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/events';
const ID = 'magewire.playwright.events.basic';

function rootTag(html, wireId) {
    const escaped = wireId.replace(/[.]/g, '\\.');
    const match = html.match(new RegExp(`<[^>]*\\bwire:id="${escaped}"[^>]*>`));
    return match ? match[0] : null;
}

function effectsFromTag(tag) {
    const match = tag.match(/wire:effects="([^"]*)"/);
    if (!match) {
        throw new Error('wire:effects attribute not present on root tag');
    }

    return JSON.parse(match[1].replace(/&quot;/g, '"'));
}

const component = page => page.locator(`[wire\\:id="${ID}"]`);

async function dispatch(page, event) {
    await page.evaluate((name) => window.dispatchEvent(new CustomEvent(name)), event);
}

test.describe('Magewire Playwright — Events', () => {
    test.beforeEach(async ({ page }) => {
        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);
    });

    test('renders the page with the correct title', async ({ page }) => {
        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Events');
    });

    test('publishes only the effective listeners in the initial browser effect', async ({ request }) => {
        const html = await (await request.get(PATH)).text();
        const tag = rootTag(html, ID);

        expect(tag).toBeTruthy();

        const listeners = effectsFromTag(tag).listeners;

        expect(listeners).toEqual(expect.arrayContaining([
            'class:kept',
            'class:replaced',
            'attribute:kept',
            'attribute:replaced',
            'layout:added',
            'layout:replaced-later',
            'modifier:resolved',
            'modifier:replace',
        ]));
        expect(listeners).not.toEqual(expect.arrayContaining([
            'class:removed',
            'shorthandListener',
            'dynamic:resolved',
            'attribute:removed',
            'layout:removed-later',
        ]));
    });

    test('merges loader entries from the layout and PHP modifier', async ({ request }) => {
        const html = await (await request.get(PATH)).text();
        const tag = rootTag(html, ID);

        expect(tag).toBeTruthy();
        expect(effectsFromTag(tag).loader[0]).toEqual({
            onClassKept: ['Loading from PHP modifier'],
            onLayoutAdded: ['Added loading'],
            onModifierAdded: ['Adding from PHP modifier'],
        });
    });

    test('uses PHP-computed listener names and handler replacements', async ({ page }) => {
        const result = component(page).getByTestId('event-result');

        await dispatch(page, 'modifier:resolved');
        await expect(result).toHaveText('modifier-added');

        await dispatch(page, 'modifier:replace');
        await expect(result).toHaveText('modifier-replacement');
    });

    test('dispatches kept and layout-added listeners', async ({ page }) => {
        const result = component(page).getByTestId('event-result');

        await dispatch(page, 'class:kept');
        await expect(result).toHaveText('class-kept');

        await dispatch(page, 'attribute:kept');
        await expect(result).toHaveText('attribute-kept');

        await dispatch(page, 'layout:added');
        await expect(result).toHaveText('layout-added');
    });

    test('uses layout handlers for replaced class, attribute, and layout listeners', async ({ page }) => {
        const result = component(page).getByTestId('event-result');

        await dispatch(page, 'class:replaced');
        await expect(result).toHaveText('class-replacement');

        await dispatch(page, 'attribute:replaced');
        await expect(result).toHaveText('attribute-replacement');

        await dispatch(page, 'layout:replaced-later');
        await expect(result).toHaveText('layout-replacement');
    });

    test('rejects a direct server dispatch to a removed listener', async ({ page }) => {
        const response = await page.evaluate(({ id, event }) => new Promise((resolve) => {
            const release = window.Livewire.hook('request', ({ fail }) => {
                fail(({ status, content, preventDefault }) => {
                    preventDefault();
                    release();
                    resolve({ status, content });
                });
            });

            window.Livewire.find(id).call('__dispatch', event, {});
        }), { id: ID, event: 'class:removed' });

        expect(response.status).toBe(500);
        expect(response.content).toContain('Handler for event class:removed does not exist');
    });
});
