import { expect } from '@playwright/test';

export const fixturesEnabled = process.env.MAGEWIRE_PLAYWRIGHT_FIXTURES === '1';
export const complexMessage = 'Complex flash message rendered after a JSON request.';

export async function queueComplexMessage(page) {
    // page.request shares the browser's cookies, so the next navigation uses the same session.
    const formKey = await page.evaluate(() => window.hyva?.getFormKey?.() || window.FORM_KEY);
    expect(formKey).toBeTruthy();

    const response = await page.request.post('/magewirefixture/index/queue', {
        form: { form_key: formKey },
    });

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/json');
    expect(await response.json()).toEqual({ queued: true });
}
