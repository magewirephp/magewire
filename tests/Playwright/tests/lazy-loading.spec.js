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
    intersectAlpine: 'magewire.playwright.lazyloading.intersect.alpine',
    host: 'magewire.playwright.lazyloading.host',
    hostChild: 'magewire.playwright.lazyloading.host.child',
    attributeOnload: 'magewire.playwright.lazyloading.attribute.onload',
    attributeOnloadOverridden: 'magewire.playwright.lazyloading.attribute.onload.overridden',
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
     * Assertions against the untouched server response — proves the placeholder and the
     * memo driving the lazy trigger are emitted on the initial paint, before any JS runs.
     */
    test.describe('server-rendered placeholder', () => {
        test('emits a template-id placeholder marked for the on-load trigger', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const tag = rootTag(html, ID.onload);

            expect(tag).toBeTruthy();
            expect(snapshotFromTag(tag).memo.lazyMode).toBe('on-load');
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
            expect(snapshotFromTag(tag).memo.lazyLoaded).toBe(false);
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
            expect(snapshotFromTag(tag).memo.lazyMode).toBe('on-intersect');
        });

        test('takes the on-load mode off the #[Lazy(mode: ...)] attribute', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const snapshot = snapshotFromTag(rootTag(html, ID.attributeOnload));

            expect(snapshot.memo.lazyLoaded).toBe(false);
            expect(snapshot.memo.lazyMode).toBe('on-load');
        });

        test('lets the layout argument override the attribute mode', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const snapshot = snapshotFromTag(rootTag(html, ID.attributeOnloadOverridden));

            expect(snapshot.memo.lazyMode).toBe('on-intersect');
        });

        test('lazies only the child of an eagerly rendered host component', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const host = snapshotFromTag(rootTag(html, ID.host));
            const child = snapshotFromTag(rootTag(html, ID.hostChild));

            // Host renders for real; only its child holds back.
            expect(host.memo.lazyLoaded ?? null).toBeNull();
            expect(html).toContain('data-testid="lazy-host-content"');

            expect(child.memo.lazyLoaded).toBe(false);
            expect(child.memo.lazyMode).toBe('on-load');

            // The child placeholder sits inside the host markup.
            expect(html.indexOf('data-testid="lazy-host-content"'))
                .toBeLessThan(html.indexOf(`wire:id="${ID.hostChild}"`));
        });

        test('injects no attributes into the placeholder root', async ({ request }) => {
            const html = await (await request.get(PATH)).text();

            // The trigger travels through the snapshot memo, so nothing may be added to the
            // root beyond wire:id / wire:snapshot / wire:effects — an injected x-data would
            // silently overrule one a developer put on their own placeholder root.
            const tag = rootTag(html, ID.intersectAlpine);

            expect(tag).toBeTruthy();
            expect(tag).toContain('x-data="magewireLazyProbe"');
            expect(tag.match(/x-data=/g)).toHaveLength(1);
            expect(tag).not.toContain('magewireLazyLoad');
            expect(tag).not.toContain('data-magewire-lazy-mode');
        });

        test('does not lazy-load the control component (no memo, real content inline)', async ({ request }) => {
            const html = await (await request.get(PATH)).text();
            const tag = rootTag(html, ID.control);

            expect(tag).toBeTruthy();
            const snapshot = snapshotFromTag(tag);
            expect(snapshot.memo.lazyLoaded ?? null).toBeNull();
            expect(snapshot.memo.lazyMode ?? null).toBeNull();
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
     * on-load components load themselves right after component initialization.
     */
    test.describe('on-load trigger', () => {
        test('replaces the placeholder with real content and runs mount() on the XHR', async ({ page }) => {
            const component = locator(page, ID.onload);

            await expect(component.getByTestId('lazy-content')).toBeVisible();
            await expect(component.getByTestId('lazy-mounted')).toHaveText('mounted');
            await expect(component.getByTestId('lazy-placeholder')).toHaveCount(0);
        });

        test('loads an #[Lazy(mode: \'on-load\')] component that never comes into view', async ({ page }) => {
            const component = locator(page, ID.attributeOnload);

            // Sits behind the spacer: only its mode can explain it loading.
            await expect(component.getByTestId('lazy-content')).toBeVisible();
            await expect(component.getByTestId('lazy-mounted')).toHaveText('mounted');
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
     * A lazy-loaded component must behave like any other afterwards: its directives are
     * bound on the morphed-in markup, not on the placeholder it replaced.
     */
    test.describe('interaction after loading', () => {
        test('handles wire:click on a lazy-loaded component', async ({ page }) => {
            const component = locator(page, ID.onload);

            await expect(component.getByTestId('lazy-count')).toHaveText('0');

            await component.getByTestId('lazy-increment').click();
            await expect(component.getByTestId('lazy-count')).toHaveText('1');

            await component.getByTestId('lazy-increment').click();
            await expect(component.getByTestId('lazy-count')).toHaveText('2');
        });

        test('syncs a checkbox through wire:model.live on a lazy-loaded component', async ({ page }) => {
            const component = locator(page, ID.onload);
            const checkbox = component.getByTestId('lazy-checkbox');

            await expect(component.getByTestId('lazy-checked')).toHaveText('unchecked');

            await checkbox.check();

            // Server-rendered text: proves the property reached the component, not just the DOM.
            await expect(component.getByTestId('lazy-checked')).toHaveText('checked');
            await expect(checkbox).toBeChecked();

            await checkbox.uncheck();

            await expect(component.getByTestId('lazy-checked')).toHaveText('unchecked');
            await expect(checkbox).not.toBeChecked();
        });

        test('keeps interaction working on a component loaded by intersection', async ({ page }) => {
            const component = locator(page, ID.intersectAttribute);

            await component.scrollIntoViewIfNeeded();
            await expect(component.getByTestId('lazy-content')).toBeVisible();

            await component.getByTestId('lazy-increment').click();
            await expect(component.getByTestId('lazy-count')).toHaveText('1');

            await component.getByTestId('lazy-checkbox').check();
            await expect(component.getByTestId('lazy-checked')).toHaveText('checked');
        });
    });

    /**
     * A lazy child inside a normally loaded parent component. The parent must render and
     * stay interactive throughout, and the child must end up as live as any other.
     */
    test.describe('lazy child of a non-lazy host', () => {
        test('renders the host immediately and swaps in the lazy child', async ({ page }) => {
            const host = locator(page, ID.host);
            const child = locator(page, ID.hostChild);

            await expect(host.getByTestId('lazy-host-mounted')).toHaveText('mounted');

            await expect(child.getByTestId('lazy-content')).toBeVisible();
            await expect(child.getByTestId('lazy-mounted')).toHaveText('mounted');
            await expect(child.getByTestId('lazy-placeholder')).toHaveCount(0);
        });

        test('keeps both components interactive and independent after the lazy commit', async ({ page }) => {
            const host = locator(page, ID.host);
            const child = locator(page, ID.hostChild);

            await expect(child.getByTestId('lazy-content')).toBeVisible();

            // The host survived the child's lazy commit with its own directives intact.
            await host.getByTestId('lazy-host-increment').click();
            await expect(host.getByTestId('lazy-host-count')).toHaveText('1');

            // The child updates on its own, without resetting the host.
            await child.getByTestId('lazy-increment').click();
            await expect(child.getByTestId('lazy-count')).toHaveText('1');
            await expect(host.getByTestId('lazy-host-count')).toHaveText('1');

            await child.getByTestId('lazy-checkbox').check();
            await expect(child.getByTestId('lazy-checked')).toHaveText('checked');

            await host.getByTestId('lazy-host-increment').click();
            await expect(host.getByTestId('lazy-host-count')).toHaveText('2');
            await expect(child.getByTestId('lazy-count')).toHaveText('1');
            await expect(child.getByTestId('lazy-checked')).toHaveText('checked');
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

            // Scroll it into view — the IntersectionObserver fires the lazy load.
            await component.scrollIntoViewIfNeeded();

            await expect(component.getByTestId('lazy-content')).toBeVisible();
            await expect(component.getByTestId('lazy-mounted')).toHaveText('mounted');
        });

        test('holds an on-load attribute component whose layout argument overrode it to on-intersect', async ({ page }) => {
            const component = locator(page, ID.attributeOnloadOverridden);

            await expect(component.getByTestId('lazy-placeholder')).toBeVisible();
            await expect(component.getByTestId('lazy-content')).toHaveCount(0);

            await component.scrollIntoViewIfNeeded();

            await expect(component.getByTestId('lazy-content')).toBeVisible();
            await expect(component.getByTestId('lazy-mounted')).toHaveText('mounted');
        });

        test('keeps a developer Alpine component alive on the placeholder root', async ({ page }) => {
            const component = locator(page, ID.intersectAlpine);

            // Placeholder root binds x-data="magewireLazyProbe"; it initializing proves the
            // lazy trigger did not take the root's x-data over.
            await expect(component.getByTestId('lazy-alpine-placeholder')).toHaveText('alive');

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