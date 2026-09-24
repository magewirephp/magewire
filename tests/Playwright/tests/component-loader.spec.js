import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { join, resolve } from 'node:path';

const PATH = '/magewire/playwright/componentloader';
const CONFIG_PATH = 'magewire/features/component_loader/show_interacted';
const MAGENTO_ROOT = process.env.MAGENTO_ROOT
    || resolve(process.cwd(), '../../../../..');

test.describe.configure({ mode: 'serial' });

function magento(...args) {
    return execFileSync(process.env.MAGENTO_PHP_BIN || 'php', [join(MAGENTO_ROOT, 'bin/magento'), ...args], {
        cwd: MAGENTO_ROOT,
        encoding: 'utf8',
    }).trim();
}

function cleanConfigCache() {
    magento('cache:clean', 'config', 'block_html', 'full_page');
}

function applyConfig(value) {
    const previous = magento('config:show', CONFIG_PATH);
    magento('config:set', CONFIG_PATH, String(value));
    cleanConfigCache();
    return previous;
}

function restoreConfig(previous) {
    if (previous === undefined) {
        return;
    }

    magento('config:set', CONFIG_PATH, previous || '0');
    cleanConfigCache();
}

async function visit(page) {
    await page.goto(`${PATH}?v=${Date.now()}`);
    await page.waitForFunction(() => (
        window.MagewireAddons?.componentLoader
        && window.MagewireUtilities?.loaderTiming
        && document.querySelector('[x-data="magewireComponentLoader"]')?._x_dataStack
        && window.Magewire?.all?.().some(component => component.el?.id === 'component-loader-origin')
        && window.Magewire?.all?.().some(component => component.el?.id === 'component-loader-listener')
    ));
}

async function gateRequests(page) {
    const release = {};
    const seen = {};
    const waiting = {
        direct: new Promise(resolve => seen.direct = resolve),
        listener: new Promise(resolve => seen.listener = resolve),
    };

    await page.route('**/magewire/update**', async route => {
        const payload = route.request().postDataJSON();
        const isListener = payload.components.some(component =>
            component.calls.some(call => call.method === '__dispatch')
        );
        const kind = isListener ? 'listener' : 'direct';

        seen[kind]();
        await new Promise(resolve => release[kind] = resolve);
        await route.continue();
    });

    return { waiting, release };
}

test('shows only the follow-up listener by default', async ({ page }) => {
    expect(['', '0']).toContain(magento('config:show', CONFIG_PATH));
    let gate;

    try {
        await visit(page);
        gate = await gateRequests(page);
        await page.getByTestId('origin-run').click();
        await gate.waiting.direct;

        await page.waitForTimeout(750);
        await expect(page.locator('#component-loader-origin .magewire-component-loader')).toHaveCount(0);
        await expect(page.locator('#component-loader-listener .magewire-component-loader')).toHaveCount(0);

        gate.release.direct();
        await gate.waiting.listener;

        await expect(page.locator('#component-loader-listener .magewire-component-loader')).toBeVisible();
        await expect(page.locator('#component-loader-origin .magewire-component-loader')).toHaveCount(0);

        gate.release.listener();
        await expect(page.getByTestId('origin-count')).toHaveText('1');
        await expect(page.getByTestId('listener-count')).toHaveText('1');
        await expect(page.locator('.magewire-component-loader')).toHaveCount(0);
    } finally {
        gate?.release.direct?.();
        gate?.release.listener?.();
    }
});

test('includes the interacted component when enabled', async ({ page }) => {
    const previous = applyConfig(1);
    let gate;

    try {
        await visit(page);
        gate = await gateRequests(page);
        await page.getByTestId('origin-run').click();
        await gate.waiting.direct;

        await expect(page.locator('#component-loader-origin .magewire-component-loader')).toBeVisible();

        gate.release.direct();
        await gate.waiting.listener;
        await expect(page.locator('#component-loader-listener .magewire-component-loader')).toBeVisible();

        gate.release.listener();
        await expect(page.getByTestId('listener-count')).toHaveText('1');
        await expect(page.locator('.magewire-component-loader')).toHaveCount(0);
    } finally {
        gate?.release.direct?.();
        gate?.release.listener?.();
        restoreConfig(previous);
    }
});

test('centers only the shared spinner and adapts to recent timing', async ({ page }) => {
    await visit(page);

    const thresholds = await page.evaluate(() => {
        const timing = window.MagewireUtilities.loaderTiming;
        const name = 'component-loader-timing-test';
        const unknown = timing.threshold(name);
        timing.record(name, 900);
        timing.record(name, 850);
        const slow = timing.threshold(name);
        timing.record(name, 40);
        timing.record(name, 50);
        timing.record(name, 60);
        return { unknown, slow, fast: timing.threshold(name) };
    });

    expect(thresholds.slow).toBeLessThanOrEqual(thresholds.unknown);
    expect(thresholds.fast).toBeGreaterThanOrEqual(thresholds.unknown);

    await page.evaluate(() => {
        const root = document.querySelector('#component-loader-origin');
        window.finishComponentLoaderTest = window.MagewireAddons.componentLoader.start({
            id: 'component-loader-manual-test',
            name: 'component-loader-manual-test',
            el: root,
        });
    });

    const root = page.locator('#component-loader-origin');
    const overlay = root.locator('.magewire-component-loader');
    await expect(overlay).toBeVisible();
    const rootBox = await root.boundingBox();
    const spinnerBox = await overlay.locator('.magewire-component-loader-spinner').boundingBox();
    expect(spinnerBox.width).toBeLessThanOrEqual(48);
    expect(spinnerBox.height).toBeLessThanOrEqual(48);
    expect(Math.abs(spinnerBox.x + spinnerBox.width / 2 - rootBox.x - rootBox.width / 2)).toBeLessThan(2);
    expect(Math.abs(spinnerBox.y + spinnerBox.height / 2 - rootBox.y - rootBox.height / 2)).toBeLessThan(2);
    expect(await overlay.evaluate(node => getComputedStyle(node).backgroundColor)).toBe('rgba(0, 0, 0, 0)');

    await page.evaluate(() => window.finishComponentLoaderTest());
    await expect(overlay).toHaveCount(0);
});
