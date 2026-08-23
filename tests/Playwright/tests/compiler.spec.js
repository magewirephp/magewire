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
        await expect(host.getByTestId('compiler-flake-card').locator('.mw-flake-badge'))
            .toHaveText('Nested tag middleware');
    });

    test('does not compile when magewire:compiler is present without magewire:compile', async ({ page }) => {
        await expect(page.getByTestId('compiler-selector-only-output'))
            .toHaveText("{{ 'selector alone does not compile' }}");
    });
});
