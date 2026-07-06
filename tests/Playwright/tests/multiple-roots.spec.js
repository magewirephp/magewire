import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import process from 'process';

const PATH = '/magewire/playwright/multipleroots';
const CONFIG_PATH = 'magewire/features/multiple_root_element_detection/behavior';
const EXPECTED_MESSAGE = 'Magewire only supports a single root element per component';

// The detection behavior is a global store config, so these tests mutate it and must run
// serially — the whole file is serial so no concurrent test observes a flipped value.
// They shell out to bin/magento, which requires the Playwright runner to be co-located
// with the Magento install (true for local dev). Override with MAGENTO_ROOT / MAGENTO_PHP_BIN.
function findMagentoRoot() {
    if (process.env.MAGENTO_ROOT) return process.env.MAGENTO_ROOT;
    let dir = process.cwd();
    while (dir !== path.dirname(dir)) {
        if (fs.existsSync(path.join(dir, 'bin', 'magento'))) return dir;
        dir = path.dirname(dir);
    }
    throw new Error('Could not locate the Magento root (bin/magento). Set MAGENTO_ROOT.');
}

const MAGENTO_ROOT = findMagentoRoot();
const PHP_BIN = process.env.MAGENTO_PHP_BIN || 'php';

function magento(args) {
    execSync(`${PHP_BIN} bin/magento ${args}`, { cwd: MAGENTO_ROOT, stdio: 'pipe' });
}

function setBehavior(value) {
    magento(`config:set ${CONFIG_PATH} ${value}`);
    magento('cache:flush config');
}

function open(page) {
    const version = Math.floor(Math.random() * 1_000_000);
    return page.goto(`${PATH}?v=${version}`);
}

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') errors.push(msg.text());
    });
    return errors;
}

test.describe('Magewire Playwright — Multiple Root Element Detection', () => {
    test.describe.configure({ mode: 'serial' });

    // Guarantee a known starting state and always restore it, so a failure mid-run never
    // leaves the store on another behavior for the next run.
    test.beforeAll(() => setBehavior('exception'));
    test.afterAll(() => setBehavior('exception'));

    // ---- Default behavior: exception (set by the top-level beforeAll) -----------------

    test('renders the page with the correct title', async ({ page }) => {
        await open(page);

        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Multiple Roots');
    });

    test.describe('Component with multiple root elements', () => {
        test('renders the inline Magewire exception block instead of the component', async ({ page }) => {
            await open(page);

            const exception = page.locator('.magewire-exception');

            await expect(exception).toBeVisible();
            await expect(exception).toContainText(EXPECTED_MESSAGE);
        });

        test('names the offending template in the exception message', async ({ page }) => {
            await open(page);

            await expect(page.locator('.magewire-exception')).toContainText('multiple_roots/invalid.phtml');
        });

        test('does not mount the broken markup as a live component', async ({ page }) => {
            await open(page);

            // The second root never receives wire:id/wire:snapshot, so no live
            // component should exist for the invalid block.
            const component = page.locator('[wire\\:id="magewire.playwright.multiple_roots.invalid"]');

            await expect(component).toHaveCount(0);
        });
    });

    test.describe('Component with a single root element', () => {
        test('renders untouched as a live component', async ({ page }) => {
            await open(page);

            const component = page.locator('[wire\\:id="magewire.playwright.multiple_roots.valid"]');

            // wire:id is injected onto the single root <div>, so the component
            // locator and #multiple-roots-valid resolve to the same element.
            await expect(component).toBeVisible();
            await expect(component).toHaveAttribute('id', 'multiple-roots-valid');
            await expect(component).toContainText('Hello World');
        });

        test('does not trigger an exception block', async ({ page }) => {
            await open(page);

            const valid = page.locator(
                '[wire\\:id="magewire.playwright.multiple_roots.valid"] .magewire-exception',
            );

            await expect(valid).toHaveCount(0);
        });
    });

    // ---- Configurable behaviors -------------------------------------------------------

    test.describe('behavior = console', () => {
        test.beforeAll(() => setBehavior('console'));

        test('renders the page (no exception block) and logs to the browser console', async ({ page }) => {
            const errors = collectConsoleErrors(page);

            await open(page);

            // Page is not aborted: no inline exception block is rendered.
            await expect(page.locator('.magewire-exception')).toHaveCount(0);

            // The single-root control still mounts as a live component.
            await expect(
                page.locator('[wire\\:id="magewire.playwright.multiple_roots.valid"]'),
            ).toBeVisible();

            // The violation surfaces as a console error instead of throwing.
            await expect
                .poll(() => errors.some((text) => text.includes(EXPECTED_MESSAGE)))
                .toBe(true);
        });
    });

    test.describe('behavior = log', () => {
        test.beforeAll(() => setBehavior('log'));

        test('renders the page with neither an exception block nor a browser console error', async ({ page }) => {
            const errors = collectConsoleErrors(page);

            await open(page);

            await expect(page.locator('.magewire-exception')).toHaveCount(0);
            await expect(
                page.locator('[wire\\:id="magewire.playwright.multiple_roots.valid"]'),
            ).toBeVisible();

            // Logged server-side only; nothing about the violation reaches the browser.
            expect(errors.some((text) => text.includes(EXPECTED_MESSAGE))).toBe(false);
        });
    });

    test.describe('behavior = off', () => {
        test.beforeAll(() => setBehavior('off'));

        test('renders the invalid markup untouched with no exception and no console error', async ({ page }) => {
            const errors = collectConsoleErrors(page);

            await open(page);

            await expect(page.locator('.magewire-exception')).toHaveCount(0);
            await expect(
                page.locator('[wire\\:id="magewire.playwright.multiple_roots.valid"]'),
            ).toBeVisible();
            expect(errors.some((text) => text.includes(EXPECTED_MESSAGE))).toBe(false);
        });
    });
});
