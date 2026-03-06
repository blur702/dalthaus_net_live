const { test, expect } = require('@playwright/test');

/**
 * Simple test to check what's on the public photobooks page
 */

test('Check public photobooks page structure', async ({ page }) => {
    console.log('\n[TEST] Checking public photobooks page...');

    await page.goto('https://dalthaus.net/photobooks');
    await page.waitForLoadState('networkidle');

    // Get page HTML
    const html = await page.content();
    console.log('[TEST] Page loaded');

    // Check if we see the "No photobooks available" message
    const hasNoPhotobooksMessage = html.includes('No photobooks available');
    console.log(`[TEST] Has "No photobooks available" message: ${hasNoPhotobooksMessage}`);

    // Try different selectors to find photobook titles
    const h2Links = await page.locator('article h2 a').all();
    const h3Links = await page.locator('article h3 a').all();
    const anyTitles = await page.locator('article h2, article h3').all();

    console.log(`[TEST] Found ${h2Links.length} <article> <h2> <a> elements`);
    console.log(`[TEST] Found ${h3Links.length} <article> <h3> <a> elements`);
    console.log(`[TEST] Found ${anyTitles.length} <article> <h2>/<h3> elements`);

    if (h3Links.length > 0) {
        console.log('[TEST] Photobook titles found:');
        for (let i = 0; i < Math.min(5, h3Links.length); i++) {
            const title = await h3Links[i].textContent();
            console.log(`  ${i + 1}. "${title.trim()}"`);
        }
    }

    // Check all h3 elements
    const allH3 = await page.locator('h3').all();
    console.log(`[TEST] Total <h3> elements on page: ${allH3.length}`);
});
