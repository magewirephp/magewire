import { defineConfig } from '@playwright/test';
import 'dotenv/config';
import process from 'process';

export default defineConfig({
    testDir: '.',
    fullyParallel: true,
    // Magewire specs drive live AJAX round-trips against a Magento store; a
    // component that is mid-hydrate on the first hit reliably settles on a
    // retry. Retry more aggressively on CI, once locally.
    retries: process.env.CI ? 2 : 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.BASE_URL.replace(/^\/+|\/+$/g, ''),
        // Local Magento hosts are served over HTTPS with a locally-trusted certificate that Node
        // does not know about, which fails every spec using the `request` fixture even while the
        // browser is perfectly happy. Test hosts are developer machines and CI containers, so
        // verifying the chain buys nothing here.
        ignoreHTTPSErrors: true,
        browserName: 'chromium',
        headless: true,
        trace: 'off',
        video: 'off',
        screenshot: 'off',
    },
});
