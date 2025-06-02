import { defineConfig } from '@playwright/test';
import 'dotenv/config';
import process from 'process';

export default defineConfig({
    testDir: '.',
    fullyParallel: true,
    reporter: [['list']],
    use: {
        baseURL: process.env.BASE_URL.replace(/^\/+|\/+$/g, ''),
        browserName: 'chromium',
        headless: true,
        trace: 'off',
        video: 'off',
        screenshot: 'off',
    },
});
