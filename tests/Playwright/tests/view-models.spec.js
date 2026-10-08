import { test, expect } from '@playwright/test';

const PATH = '/magewire/playwright/viewmodels';
const ID = 'magewire.playwright.view-models.component';
const VIEW_MODEL = 'Magewirephp\\Magewire\\ViewModel\\Playwright\\UiPreview';
const BEFORE = 'magewire.playwright.view-models.before';
const AFTER = 'magewire.playwright.view-models.after';
const ROOT = 'magewire.playwright.view-models.root';

test.describe('Magewire Playwright — View models', () => {
    test('keeps custom view models before and after the root and during updates', async ({ page }) => {
        const response = await page.goto(`${PATH}?v=${Date.now()}`);
        expect(response.status()).toBe(200);
        expect(response.request().redirectedFrom()).toBeNull();

        for (const id of [BEFORE, AFTER, ID]) {
            await expect(page.getByTestId(id)).toBeVisible();
            await expect(page.getByTestId(id)).toHaveAttribute('data-view-model', VIEW_MODEL);
            await expect(page.getByTestId(id).getByTestId('view-model-mode')).toHaveText(/^(developer|default)$/);
        }

        // The root still receives Magewire's view model and initializes its scripts.
        await page.waitForFunction(id => window.Magewire?.all?.()?.some(item => item.id === id), ID);
        const order = await page.evaluate(({ before, after, root }) => {
            const first = document.querySelector(`[data-testid="${before}"]`);
            const last = document.querySelector(`[data-testid="${after}"]`);
            const marker = document.querySelector(`[data-testid="${root}"]`);

            return Boolean(first.compareDocumentPosition(marker) & Node.DOCUMENT_POSITION_FOLLOWING)
                && Boolean(marker.compareDocumentPosition(last) & Node.DOCUMENT_POSITION_FOLLOWING);
        }, { before: BEFORE, after: AFTER, root: ROOT });
        expect(order).toBe(true);

        const component = page.getByTestId(ID);
        await expect(component.getByTestId('view-model-result')).toHaveText('none');
        const [update] = await Promise.all([
            page.waitForResponse(response => response.request().method() === 'POST'
                && new URL(response.url()).pathname.endsWith('/magewire/update')),
            page.evaluate(id => window.Magewire.find(id).call('onClassKept'), ID),
        ]);

        expect(update.status()).toBe(200);
        expect(update.request().redirectedFrom()).toBeNull();
        expect(update.headers()['content-type']).toContain('application/json');
        const payload = await update.json();
        expect(payload.components).toHaveLength(1);
        const responseViewModel = await page.evaluate(html => {
            const document = new DOMParser().parseFromString(html, 'text/html');
            return document.querySelector('[data-view-model]').getAttribute('data-view-model');
        }, payload.components[0].effects.html);
        expect(responseViewModel).toBe(VIEW_MODEL);
        await expect(component.getByTestId('view-model-result')).toHaveText('class-kept');
        await expect(component).toHaveAttribute('data-view-model', VIEW_MODEL);
    });
});
