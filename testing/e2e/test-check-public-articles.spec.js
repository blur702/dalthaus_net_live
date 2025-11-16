const { test, expect } = require('@playwright/test');

/**
 * Simple test to check what's on the public articles page
 */

test('Check public articles page structure', async ({ page }) => {
    console.log('\n[TEST] Checking public articles page...');

    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');

    // Get page HTML
    const html = await page.content();
    console.log('[TEST] Page loaded');

    // Check if we see the "No articles available" message
    const hasNoArticlesMessage = html.includes('No articles available');
    console.log(`[TEST] Has "No articles available" message: ${hasNoArticlesMessage}`);

    // Try different selectors to find article titles
    const h2Links = await page.locator('article h2 a').all();
    const h3Links = await page.locator('article h3 a').all();
    const anyArticleTitles = await page.locator('article h2, article h3').all();

    console.log(`[TEST] Found ${h2Links.length} <article> <h2> <a> elements`);
    console.log(`[TEST] Found ${h3Links.length} <article> <h3> <a> elements`);
    console.log(`[TEST] Found ${anyArticleTitles.length} <article> <h2>/<h3> elements`);

    if (h3Links.length > 0) {
        console.log('[TEST] Article titles found:');
        for (let i = 0; i < Math.min(5, h3Links.length); i++) {
            const title = await h3Links[i].textContent();
            console.log(`  ${i + 1}. "${title.trim()}"`);
        }
    }

    // Check all h3 elements
    const allH3 = await page.locator('h3').all();
    console.log(`[TEST] Total <h3> elements on page: ${allH3.length}`);

    if (allH3.length > 0 && h3Links.length === 0) {
        console.log('[TEST] Found h3 elements but not inside articles. Checking all h3:');
        for (let i = 0; i < Math.min(5, allH3.length); i++) {
            const text = await allH3[i].textContent();
            console.log(`  ${i + 1}. "${text.trim()}"`);
        }
    }
});
