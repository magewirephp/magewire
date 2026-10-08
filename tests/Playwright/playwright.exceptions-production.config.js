import { defineConfig } from '@playwright/test';
import config from './playwright.config.js';

if (process.env.MAGEWIRE_PLAYWRIGHT_FIXTURES !== '1') {
    throw new Error('Production exception checks require MAGEWIRE_PLAYWRIGHT_FIXTURES=1 and the test-only fixture module.');
}

// Run after the developer suite finishes and the isolated CI store switches to production.
export default defineConfig({
    ...config,
    metadata: { magentoMode: 'production' },
    testMatch: '**/exceptions-environments.spec.js',
});
