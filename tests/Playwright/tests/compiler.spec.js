import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/compiler';
const DEFAULT_COMPILER = 'Magewirephp\\Magewire\\Mechanisms\\HandleCompiling\\View\\Compiler\\MagentoTemplateCompiler';
const CUSTOM_COMPILER = 'Magewirephp\\Magewire\\Playwright\\Compiler\\CustomCompiler';

test.describe('Magewire Playwright — Compiler', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(PATH);
    });

    test('renders the dedicated compiler route', async ({ page }) => {
        await expect(page).toHaveTitle('Magewire / Playwright / Compiler');
        await expect(page.locator('[data-ui-id="page-title-wrapper"]'))
            .toHaveText('Magewire / Playwright / Compiler');
    });

    test('compiles an ordinary block with the default compiler and no Magewire snapshot', async ({ page }) => {
        const host = page.getByTestId('compiler-default');

        await expect(host).toHaveAttribute('data-compiler', DEFAULT_COMPILER);
        await expect(host).not.toHaveAttribute('wire:id', /.+/);
        await expect(host).not.toHaveAttribute('wire:snapshot', /.+/);
        await expect(host.locator('li')).toHaveText([
            'First compiled item',
            'Last compiled item',
        ]);
        await expect(host.locator('[data-variant-key="hidden"]')).toHaveCount(0);
    });

    test('uses an explicit custom Compiler subclass', async ({ page }) => {
        const host = page.getByTestId('compiler-custom');

        await expect(host).toHaveAttribute('data-compiler', CUSTOM_COMPILER);
        await expect(host.getByTestId('compiler-custom-output')).toHaveText('Custom compiler selected');
        await expect(host).not.toHaveAttribute('wire:id', /.+/);
    });

    test('compiles nested @if and @foreach directive scopes on an ordinary block', async ({ page }) => {
        const host = page.getByTestId('compiler-directives');

        await expect(host).not.toHaveAttribute('wire:id', /.+/);
        await expect(host).not.toHaveAttribute('wire:snapshot', /.+/);
        await expect(host.getByTestId('compiler-directive-branch')).toHaveText('Secondary branch');
        await expect(host.getByTestId('compiler-directive-items').locator('li')).toHaveText([
            'First directive item',
            'Last directive item',
        ]);
        await expect(host.getByText('Hidden directive item')).toHaveCount(0);
    });

    test('compiles @json and @translate directives without a Magewire lifecycle', async ({ page }) => {
        const host = page.getByTestId('compiler-directives');
        const payload = JSON.parse(await host.getAttribute('data-payload'));

        expect(payload).toEqual({ route: 'compiler', branch: 'secondary', count: 3 });
        await expect(host.getByTestId('compiler-directive-translation'))
            .toHaveText('Translated compiler output');
        await expect(host.locator('[wire\\:id]')).toHaveCount(0);
    });

    test('keeps escaped and raw compiler echos distinct', async ({ page }) => {
        const escaped = page.getByTestId('compiler-directive-escaped');
        const raw = page.getByTestId('compiler-directive-raw');

        await expect(escaped).toHaveText('<strong>Escaped compiler output</strong>');
        await expect(escaped.locator('strong')).toHaveCount(0);
        await expect(raw.locator('strong')).toHaveText('Raw compiler output');
    });

    test('keeps compiled and plain nested blocks on the active layout lifecycle', async ({ page }) => {
        const parent = page.getByTestId('compiler-nesting-parent');
        const child = parent.getByTestId('compiler-nesting-child');
        const grandchild = child.getByTestId('compiler-nesting-grandchild');
        const plain = parent.getByTestId('compiler-nesting-plain-child');

        await expect(parent).toHaveAttribute('data-within-parent', 'yes');
        await expect(child).toHaveAttribute('data-within-parent', 'yes');
        await expect(grandchild).toHaveAttribute('data-within-parent', 'yes');
        await expect(plain).toHaveAttribute('data-within-parent', 'yes');
        await expect(child).toHaveAttribute('data-compiler', CUSTOM_COMPILER);
        await expect(child.getByTestId('compiler-nesting-item')).toHaveText([
            'nested one',
            'nested two',
        ]);
        await expect(grandchild).toHaveText('Compiled grandchild');
        await expect(plain).toHaveText('Plain uncompiled child');

        const [parentRoute, childRoute, grandchildRoute, plainRoute] = await Promise.all([
            parent.getAttribute('data-layout-route'),
            child.getAttribute('data-layout-route'),
            grandchild.getAttribute('data-layout-route'),
            plain.getAttribute('data-layout-route'),
        ]);

        expect(childRoute).toContain(`${parentRoute}/magewire.playwright.compiler.nesting.child`);
        expect(grandchildRoute).toContain(`${childRoute}/magewire.playwright.compiler.nesting.grandchild`);
        expect(plainRoute).toContain(`${parentRoute}/magewire.playwright.compiler.nesting.plain`);
    });

    test('runs nested Flake tag middleware from a stateless host block', async ({ page }) => {
        const host = page.getByTestId('compiler-flake-host');

        await expect(host).not.toHaveAttribute('wire:id', /.+/);
        await expect(host.getByTestId('compiler-flake-card').locator('.mw-flake-heading'))
            .toHaveText('Compiled Flake tree');
        const badges = host.getByTestId('compiler-flake-card').locator('.mw-flake-badge');

        await expect(badges).toHaveCount(3);
        await expect(badges).toHaveText([
            'Nested tag middleware',
            'Nested tag middleware',
            'Nested tag middleware',
        ]);
    });

    test('does not compile when magewire:compiler is present without magewire:compile', async ({ page }) => {
        await expect(page.getByTestId('compiler-selector-only-output'))
            .toHaveText("{{ 'selector alone does not compile' }}");
    });
});
