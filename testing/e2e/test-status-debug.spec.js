const { test, expect } = require('@playwright/test');

/**
 * Debug test to check status values
 */

test('Check status values after save', async ({ page }) => {
    console.log('\n[TEST] Debugging status values...');

    // Login
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard');
    console.log('[STEP 1] ✅ Logged in');

    // Go to a published article
    await page.goto('https://dalthaus.net/admin/content');
    await page.waitForLoadState('networkidle');

    // Find first published item
    const firstPublished = page.locator('.bg-green-100.text-green-800').first();
    const publishedRow = firstPublished.locator('xpath=ancestor::tr');
    const editLink = publishedRow.locator('a:has-text("Edit")').first();
    const title = await publishedRow.locator('td').nth(1).textContent();

    console.log(`\n[BEFORE] Selected: "${title.trim()}"`);

    // Check status in the row
    const allCells = await publishedRow.locator('td').all();
    console.log(`[BEFORE] Row has ${allCells.length} cells`);

    for (let i = 0; i < allCells.length; i++) {
        const text = await allCells[i].textContent();
        console.log(`[BEFORE]   Cell ${i}: "${text.trim().substring(0, 50)}..."`);
    }

    // Go to edit page
    await editLink.click();
    await page.waitForLoadState('networkidle');
    console.log('\n[EDIT PAGE]');

    // Check hidden status field value
    const hiddenStatus = await page.locator('input[name="status"]').getAttribute('value');
    console.log(`[EDIT PAGE] Hidden status field value: "${hiddenStatus}"`);

    // Check which action buttons exist
    const buttons = await page.locator('button[name="action"]').all();
    console.log(`[EDIT PAGE] Found ${buttons.length} action buttons:`);
    for (const button of buttons) {
        const value = await button.getAttribute('value');
        const text = await button.textContent();
        console.log(`[EDIT PAGE]   - value="${value}", text="${text.trim()}"`);
    }

    // Click Save Changes
    await page.locator('button[value="save"]').click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    console.log('\n[AFTER SAVE]');

    // Go back to content list
    await page.goto('https://dalthaus.net/admin/content');
    await page.waitForLoadState('networkidle');

    // Find the same row
    const rowAfter = page.locator(`tr:has-text("${title.trim()}")`).first();
    const cellsAfter = await rowAfter.locator('td').all();

    console.log(`[AFTER] Row has ${cellsAfter.length} cells`);
    for (let i = 0; i < cellsAfter.length; i++) {
        const text = await cellsAfter[i].textContent();
        console.log(`[AFTER]   Cell ${i}: "${text.trim().substring(0, 50)}..."`);
    }

    // Look for status badges
    const greenBadges = await rowAfter.locator('.bg-green-100.text-green-800').all();
    const yellowBadges = await rowAfter.locator('.bg-yellow-100.text-yellow-800').all();

    console.log(`\n[AFTER] Found ${greenBadges.length} green badges, ${yellowBadges.length} yellow badges`);

    for (const badge of greenBadges) {
        const text = await badge.textContent();
        console.log(`[AFTER]   Green: "${text.trim()}"`);
    }

    for (const badge of yellowBadges) {
        const text = await badge.textContent();
        console.log(`[AFTER]   Yellow: "${text.trim()}"`);
    }
});
