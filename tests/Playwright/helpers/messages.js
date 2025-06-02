import process from "process";

/**
 * Verifies whether the specified message was displayed as a flash message.
 */
export async function has(page, message) {
    await hasAny(page).then(async result => {
        if (result) {
            const messages = await page.$$eval('.messages div', elements =>
                elements.map(el => el.textContent.trim())
            );

            if (messages.some(text => text.includes(message))) {
                return true;
            }
        }
    })

    return false;
}

/**
 * Verifies whether there are any messages been shown.
 */
export async function hasAny(page) {
    const messages = page.locator('#messages');

    if (messages.isVisible()) {
        const messages = await page.$$eval('.messages div', elements =>
            elements.map(el => el.textContent.trim())
        );

        return messages.length !== 0;
    }

    return false;
}
