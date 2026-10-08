import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { join } from 'node:path';

const ID = 'magewire.playwright.events.basic';
const STORE_VIEWS = ['magewire_en', 'magewire_nl'];

// This suite changes global URL configuration. Run it separately on a disposable CI store.
test.describe('Magewire Playwright — Store views', () => {
    test.skip(process.env.MAGEWIRE_MULTI_STORE_VIEW !== '1', 'Requires the dedicated store-view CI step.');
    test.describe.configure({ mode: 'serial', retries: 0, timeout: 120_000 });

    function magento(...args) {
        return execFileSync(process.env.MAGENTO_PHP_BIN || 'php', [
            join(process.env.MAGENTO_ROOT, 'bin/magento'), ...args,
        ], { cwd: process.env.MAGENTO_ROOT, encoding: 'utf8' });
    }

    for (const storeCodes of [false, true]) {
        for (const rewrites of [true, false]) {
            test(`switches store views with codes ${storeCodes ? 'on' : 'off'} and rewrites ${rewrites ? 'on' : 'off'}`, async ({ page, context, baseURL }) => {
                magento('config:set', 'web/url/use_store', storeCodes ? '1' : '0');
                magento('config:set', 'web/seo/use_rewrites', rewrites ? '1' : '0');
                magento('cache:flush');

                const base = new URL(baseURL.endsWith('/') ? baseURL : `${baseURL}/`);

                // Return to the first view to catch an update URI retained from a previous page.
                for (const code of [...STORE_VIEWS, STORE_VIEWS[0]]) {
                    const otherCode = STORE_VIEWS.find(view => view !== code);
                    await context.addCookies([{
                        name: 'store',
                        value: storeCodes ? otherCode : code,
                        url: base.origin,
                    }]);

                    const prefix = `${rewrites ? '' : 'index.php/'}${storeCodes ? `${code}/` : ''}`;
                    const updateUrl = new URL(`${prefix}magewire/update`, base);
                    const response = await page.goto(new URL(`${prefix}magewire/playwright/events?v=${Date.now()}`, base).href);

                    expect(response.status()).toBe(200);
                    expect(response.request().redirectedFrom()).toBeNull();
                    await expect(page.getByTestId('event-store-view')).toHaveText(code);
                    await page.waitForFunction(id => window.Magewire?.all?.()?.some(item => item.id === id), ID, { timeout: 20_000 });
                    await expect(page.locator('[data-update-uri]').first()).toHaveAttribute('data-update-uri', updateUrl.pathname);

                    for (const [method, result] of [['onClassKept', 'class-kept'], ['onLayoutAdded', 'layout-added']]) {
                        const [update] = await Promise.all([
                            page.waitForResponse(response => response.request().method() === 'POST'
                                && new URL(response.url()).pathname.endsWith('/magewire/update')),
                            page.evaluate(({ id, method }) => {
                                window.Magewire.find(id).call(method);
                            }, { id: ID, method }),
                        ]);

                        expect(update.url()).toBe(updateUrl.href);
                        expect(update.status()).toBe(200);
                        expect(update.request().redirectedFrom()).toBeNull();
                        expect(update.headers()['content-type']).toContain('application/json');
                        const payload = await update.json();
                        expect(payload.components).toHaveLength(1);
                        expect(payload.components[0].effects.html).toContain(`data-testid="event-store-view">${code}</p>`);
                        await expect(page.getByTestId('event-result')).toHaveText(result);
                        await expect(page.getByTestId('event-store-view')).toHaveText(code);
                    }
                }
            });
        }
    }
});
