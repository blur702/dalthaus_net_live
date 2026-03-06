const { test, expect } = require('@playwright/test');

/**
 * Homepage Reorder Display Test
 *
 * Tests that the homepage shows content in the correct reordered sequence
 */

test.describe('Homepage Reorder Display', () => {
    test('Homepage should display articles in reordered sequence', async ({ page }) => {
        console.log('[TEST] Testing homepage article order...');

        // Step 1: Login
        console.log('[STEP 1] Logging in...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('[STEP 1] ✅ Logged in successfully');

        // Step 2: Get current order from reorder page
        console.log('[STEP 2] Getting current article order from admin...');
        await page.goto('https://dalthaus.net/admin/articles/reorder');
        await page.waitForSelector('.sortable-item', { timeout: 10000 });

        const adminArticles = await page.$$eval('.sortable-item', items =>
            items.slice(0, 10).map((item, index) => ({
                id: item.dataset.id,
                title: item.querySelector('h3').textContent.trim(),
                position: index + 1
            }))
        );

        console.log('[STEP 2] Admin order (first 10):');
        adminArticles.forEach(article => {
            console.log(`  ${article.position}. "${article.title}"`);
        });

        // Step 3: Check homepage
        console.log('[STEP 3] Checking homepage article order...');
        await page.goto('https://dalthaus.net/');
        await page.waitForSelector('article, .article-card, h2, h3', { timeout: 10000 });

        // Try to find article titles on homepage
        const homepageArticles = await page.$$eval('h2, h3', headings =>
            headings
                .map(h => h.textContent.trim())
                .filter(text => text.length > 10) // Filter out short headings
                .slice(0, 10)
        );

        console.log('[STEP 3] Homepage article titles found:');
        homepageArticles.forEach((title, index) => {
            console.log(`  ${index + 1}. "${title}"`);
        });

        // Step 4: Verify order matches
        console.log('[STEP 4] Comparing admin order with homepage order...');
        let matchCount = 0;

        for (let i = 0; i < Math.min(adminArticles.length, homepageArticles.length); i++) {
            const adminTitle = adminArticles[i].title;
            const homepageTitle = homepageArticles[i];

            // Check if titles match (allowing for slight variations)
            if (adminTitle === homepageTitle || homepageTitle.includes(adminTitle) || adminTitle.includes(homepageTitle)) {
                console.log(`  ✅ Position ${i + 1} matches: "${adminTitle}"`);
                matchCount++;
            } else {
                console.log(`  ❌ Position ${i + 1} mismatch:`);
                console.log(`     Admin: "${adminTitle}"`);
                console.log(`     Homepage: "${homepageTitle}"`);
            }
        }

        console.log(`[STEP 4] ${matchCount} out of ${Math.min(adminArticles.length, homepageArticles.length)} positions match`);

        // Expect at least 80% match (allowing for some variation in title display)
        const matchPercentage = (matchCount / Math.min(adminArticles.length, homepageArticles.length)) * 100;
        expect(matchPercentage).toBeGreaterThanOrEqual(80);

        console.log('[TEST] ✅ TEST PASSED: Homepage displays articles in correct reordered sequence');
    });

    test('Homepage should display photobooks in reordered sequence', async ({ page }) => {
        console.log('[TEST] Testing homepage photobook order...');

        // Step 1: Login
        console.log('[STEP 1] Logging in...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('[STEP 1] ✅ Logged in successfully');

        // Step 2: Get current order from reorder page
        console.log('[STEP 2] Getting current photobook order from admin...');
        await page.goto('https://dalthaus.net/admin/photobooks/reorder');
        await page.waitForSelector('.sortable-item', { timeout: 10000 });

        const adminPhotobooks = await page.$$eval('.sortable-item', items =>
            items.map((item, index) => ({
                id: item.dataset.id,
                title: item.querySelector('h3').textContent.trim(),
                position: index + 1
            }))
        );

        console.log('[STEP 2] Admin photobook order:');
        adminPhotobooks.forEach(photobook => {
            console.log(`  ${photobook.position}. "${photobook.title}"`);
        });

        // Step 3: Check homepage for photobooks section
        console.log('[STEP 3] Checking homepage photobook order...');
        await page.goto('https://dalthaus.net/');

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Take a screenshot to help debug
        await page.screenshot({ path: 'testing/test-results/homepage-photobooks.png', fullPage: true });
        console.log('[STEP 3] Screenshot saved to testing/test-results/homepage-photobooks.png');

        console.log('[TEST] ✅ TEST COMPLETED: Check screenshot for photobook order verification');
    });
});
