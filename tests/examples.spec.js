const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }, testInfo) => {
    await page.goto('/magewire/examples');
});

test('Test that the todo behaves as expected', async ({ page }) => {
    await page.getByPlaceholder('Task Description...').fill('First task');
    await page.getByRole('button', { name: 'Save' }).click();

    await page.getByPlaceholder('Task Description...').fill('Second task');
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page.getByText('First task')).toBeVisible();
    await expect(page.getByText('Second task')).toBeVisible();
});

test('Testing the shuffle function', async ({ page }) => {
    async function getOrder() {
        return page.evaluate(() => {
            return Array.from(document.querySelectorAll('[wire\\:key]')).map(el => el.getAttribute('wire:key'));
        });
    }

    // Capture original order
    const originalOrder = await getOrder();

    // Perform the action that triggers shuffling
    await page.getByRole('button', { name: 'Shuffle' }).click();

    // Unsure to watch for al element so wait for 1 second.
    await page.waitForTimeout(1000);

    // Capture new order
    await expect((await getOrder()).toString()).not.toEqual(originalOrder.toString());
});

test('Test that we can click the next and previous button', async ({ page }) => {
    let paginationCount = page.locator('#pagination td.font-bold');

    await expect(paginationCount.getByText('0', { exact: true })).toBeVisible();

    await page.getByRole('button', { name: '»' }).click();
    await expect(page.getByRole('cell', { name: '10' }).first()).toBeVisible();

    await page.getByRole('button', { name: '»' }).click();
    await expect(page.getByRole('cell', { name: '20' }).first()).toBeVisible();

    await page.getByRole('button', { name: '«' }).click();
    await expect(page.getByRole('cell', { name: '10' }).first()).toBeVisible();
});

test('Test that input will not validate with invalid data', async ({ page }) => {
    // Skipping the lastname + email on purpose
    await page.getByPlaceholder('Firstname').fill('John');

    await page.getByRole('button', { name: 'Validate' }).click();
    await expect(page.getByText('Your lastname is required')).toBeVisible();
    await expect(page.getByText('Your email is required')).toBeVisible();
});

test('Test that email addresses need to be in the correct format', async ({ page }) => {
    await page.getByPlaceholder('Firstname').fill('John');
    await page.getByPlaceholder('Lastname').fill('Doe');
    await page.getByPlaceholder('Email', { exact: true }).fill('invalid value');

    await page.getByRole('button', { name: 'Validate' }).click();
    await expect(page.getByText('Your email is not valid email')).toBeVisible();
});

test('Test that input verification works', async ({ page }) => {
    await page.getByPlaceholder('Firstname').fill('John');
    await page.getByPlaceholder('Lastname').fill('Doe');
    await page.getByPlaceholder('Email', { exact: true }).fill('Email@email.email');

    await page.getByRole('button', { name: 'Validate' }).click();
    await expect(page.getByText('Validation success!')).toBeVisible();
});

test('Test that the add/subtract buttons work as expected', async ({ page }) => {
    let number = page.locator('#reacticon');
    let button = page.getByRole('button', { name: '+1' });
    let button2 = page.getByRole('button', { name: '−1' });

    // Go from 1 (start position), add to, so we get 3.
    await button.click();
    await button.click();
    await expect(number.getByText('3')).toBeVisible();

    // Subtract 1, should be 2
    await button2.click();
    await expect(number.getByText('2')).toBeVisible();
});

