import { test , expect } from '@playwright/test';
import { has as hasMessage } from '../messages';
import process from 'process';

/**
 * Ensures user authentication by either creating a new account or logging in if the account already exists.
 * After authentication, the function returns to the original page.
 */
export async function create(page) {
    const current = page.url();
    const messages = page.locator('#messages');

    await page.goto('/customer/account/create');

    await page.fill('#firstname', process.env.ACCOUNT_FIRSTNAME);
    await page.fill('#lastname', process.env.ACCOUNT_LASTNAME);
    await page.fill('#email_address', process.env.ACCOUNT_EMAIL);
    await page.fill('#password', process.env.ACCOUNT_PASSWORD);
    await page.fill('#password-confirmation', process.env.ACCOUNT_PASSWORD);

    await page.click('.action.submit');

    await hasMessage(page, 'There is already an account with this email address. If you are sure that it is your email address, click here to get your password and access your account.').then(async () => {
        await login(page)
    })

    await page.goto(current);
}

/**
 * Logs out the customer to ensure session termination.
 * After authentication, the function returns to the original page.
 */
export async function logout(page) {
    const current = page.url();

    await page.goto('/customer/account/logout');
    await page.goto(current)
}
