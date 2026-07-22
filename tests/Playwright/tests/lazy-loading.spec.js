import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/lazyloading';

const ID = {
    control: 'magewire.playwright.lazyloading.control',
    onload: 'magewire.playwright.lazyloading.onload',
    raw: 'magewire.playwright.lazyloading.raw',
    bundled: 'magewire.playwright.lazyloading.bundled',
    bundled2: 'magewire.playwright.lazyloading.bundled2',
    intersectAttribute: 'magewire.playwright.lazyloading.intersect.attribute',
    intersectArgument: 'magewire.playwright.lazyloading.intersect.argument',
};

/**
 * Read and JSON-parse the `wire:snapshot` attribute of a live locator (the browser
 * decodes the HTML entities for us).
 */
async function readSnapshot(locator) {
    const raw = await locator.getAttribute('wire:snapshot');
    if (raw === null) {
        throw new Error('wire:snapshot attribute not present on locator');
    }
    return JSON.parse(raw);
}

/**
 * Pull a component's server-rendered root opening tag straight out of the raw HTML,
 * before any Alpine/Magewire JS has run.
 */
function rootTag(html, wireId) {
    const escaped = wireId.replace(/[.]/g, '\\.');
    const match = html.match(new RegExp(`<[^>]*\\bwire:id="${escaped}"[^>]*>`));
    return match ? match[0] : null;
}

/**
 * Decode + parse the wire:snapshot carried on a raw-HTML root tag.
 */
function snapshotFromTag(tag) {
    const match = tag.match(/wire:snapshot="([^"]*)"/);
    if (!match) {
        throw new Error('wire:snapshot attribute not present on root tag');
    }
    return JSON.parse(match[1].replace(/&quot;/g, '"'));
}

const locator = (page, wireId) => page.locator(`[wire\\:id="${wireId}"]`);

test.describe('Magewire Playwright — Lazy Loading', () => {
    test.beforeEach(async ({ page }) => {
        const version = Math.floor(Math.random() * 1_000_000);
        await page.goto(`${PATH}?v=${version}`);
    });

    test('renders the page with the correct title', async ({ page }) => {
        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Lazy Loading');
    });

    /**
     * Assertions against the untouched server response — proves the placeholder and
     * the lazy trigger are emitted on the initial paint, before any JS runs.
     */
    test.describe('server-rendered placeholder', () => {
        test('emits a template-id placeholder wired to the on-load lazy trigger', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const tag = rootTag(html, ID.onload);

            expect(tag).toBeTruthy();
            expect(tag).toContain('x-data="magewireLazyLoad"');
            expect(tag).toContain('data-magewire-lazy-mode="on-load"');
            expect(html).toContain('data-testid="lazy-placeholder"');
        });

        test('marks the on-load snapshot memo as not-yet-lazy-loaded and isolated by default', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const snapshot = snapshotFromTag(rootTag(html, ID.onload));

            expect(snapshot.memo.lazyLoaded).toBe(false);
            expect(snapshot.memo.lazyIsolated).toBe(true);
        });

        test('emits a raw-HTML placeholder for a component returning markup directly', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const tag = rootTag(html, ID.raw);

            expect(tag).toBeTruthy();
            expect(tag).toContain('x-data="magewireLazyLoad"');
            expect(html).toContain('data-testid="lazy-raw-placeholder"');
        });

        test('reports lazyIsolated=false for a #[Lazy(isolate: false)] component', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const snapshot = snapshotFromTag(rootTag(html, ID.bundled));

            expect(snapshot.memo.lazyLoaded).toBe(false);
            expect(snapshot.memo.lazyIsolated).toBe(false);
        });

        test('marks the #[Lazy] component for the on-intersect trigger', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const tag = rootTag(html, ID.intersectAttribute);

            expect(tag).toBeTruthy();
            expect(tag).toContain('x-data="magewireLazyLoad"');
            expect(tag).toContain('data-magewire-lazy-mode="on-intersect"');
        });

        test('does not lazy-load the control component (no trigger, real content inline)', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const tag = rootTag(html, ID.control);

            expect(tag).toBeTruthy();
            expect(tag).not.toContain('magewireLazyLoad');
            expect(tag).not.toContain('data-magewire-lazy-mode');
            const snapshot = snapshotFromTag(tag);
            expect(snapshot.memo.lazyLoaded ?? null).toBeNull();
        });
    });

    /**
     * The control mounts + renders eagerly on first paint.
     */
    test.describe('non-lazy control', () => {
        test('renders real content immediately with mount() having run', async ({ page }) => {
            const component = locator(page, ID.control);

            await expect(component.getByTestId('lazy-content')).toBeVisible();
            await expect(component.getByTestId('lazy-mounted')).toHaveText('mounted');
        });
    });

    /**
     * on-load (x-init) components load themselves right after the initial paint.
     */
    test.describe('on-load trigger', () => {
        test('replaces the placeholder with real content and runs mount() on the XHR', async ({ page }) => {
            const component = locator(page, ID.onload);

            await expect(component.getByTestId('lazy-content')).toBeVisible();
            await expect(component.getByTestId('lazy-mounted')).toHaveText('mounted');
            await expect(component.getByTestId('lazy-placeholder')).toHaveCount(0);
        });

        test('flips the snapshot memo lazyLoaded flag to true on the lazy XHR', async ({ page }) => {
            // The post-morph DOM carries no wire:snapshot attribute, so assert against the
            // lazy XHR responses. Collect every returned component snapshot across all update
            // responses (avoids racing a single response against navigation).
            const lazyLoadedFlags = [];
            page.on('response', async (r) => {
                if (r.url().includes('/magewire/update') && r.status() === 200) {
                    try {
                        const body = await r.json();
                        body.components.forEach((c) => {
                            lazyLoadedFlags.push(JSON.parse(c.snapshot).memo.lazyLoaded);
                        });
                    } catch (e) {
                        // Ignore non-JSON bodies.
                    }
                }
            });

            await page.goto(`${PATH}?v=${Math.floor(Math.random() * 1_000_000)}`);
            await page.waitForLoadState('networkidle');

            expect(lazyLoadedFlags).toContain(true);
        });
    });

    /**
     * Commit isolation. isolate:true (default) keeps a lazy commit in its own pool; two
     * isolated commits can therefore never travel together. isolate:false commits are
     * allowed to bundle, but whether they actually share a request depends on same-tick
     * commit batching, which is timing-dependent — so only the isolation invariant is
     * asserted strictly here (the server-side lazyIsolated flag is covered above).
     */
    test.describe('commit isolation', () => {
        // Map a /magewire/update POST body to the list of component wire:ids it carries.
        const idsOf = (postData) =>
            JSON.parse(postData).components.map((c) => JSON.parse(c.snapshot).memo.id);

        test('never delivers two isolated (default) lazy commits in the same request', async ({ page }) => {
            const requests = [];
            page.on('request', (r) => {
                if (r.url().includes('/magewire/update') && r.method() === 'POST') {
                    try {
                        requests.push(idsOf(r.postData()));
                    } catch (e) {
                        // Ignore non-JSON bodies.
                    }
                }
            });

            await page.goto(`${PATH}?v=${Math.floor(Math.random() * 1_000_000)}`);
            await page.waitForLoadState('networkidle');

            expect(requests.length).toBeGreaterThanOrEqual(1);

            // onload and raw are both isolate:true; isolation forbids them from sharing a pool.
            const isolatedShareRequest = requests.some(
                (ids) => ids.includes(ID.onload) && ids.includes(ID.raw),
            );
            expect(isolatedShareRequest).toBe(false);
        });
    });

    /**
     * on-intersect (#[Lazy] attribute) components stay as placeholders until scrolled
     * into view. They start below the fold behind a tall spacer.
     */
    test.describe('on-intersect trigger', () => {
        test('holds the placeholder until the component scrolls into view', async ({ page }) => {
            const component = locator(page, ID.intersectAttribute);

            // Still below the fold: placeholder shown, real content absent.
            await expect(component.getByTestId('lazy-placeholder')).toBeVisible();
            await expect(component.getByTestId('lazy-content')).toHaveCount(0);

            const snapshot = await readSnapshot(component);
            expect(snapshot.memo.lazyLoaded).toBe(false);

            // Scroll it into view — x-intersect fires the lazy load.
            await component.scrollIntoViewIfNeeded();

            await expect(component.getByTestId('lazy-content')).toBeVisible();
            await expect(component.getByTestId('lazy-mounted')).toHaveText('mounted');
        });

        test('lazy-loads a component opted in via the magewire:component:lazy="true" layout argument', async ({ page }) => {
            const component = locator(page, ID.intersectArgument);

            await expect(component.getByTestId('lazy-placeholder')).toBeVisible();

            await component.scrollIntoViewIfNeeded();

            await expect(component.getByTestId('lazy-content')).toBeVisible();
            await expect(component.getByTestId('lazy-mounted')).toHaveText('mounted');
        });
    });
});