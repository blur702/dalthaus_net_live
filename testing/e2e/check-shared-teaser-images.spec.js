const { test, expect } = require('@playwright/test');

/**
 * Check if multiple photobooks are sharing the same teaser image paths
 */

test.describe('Check Shared Teaser Images', () => {
    test.beforeEach(async ({ page }) => {
        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    });

    test('Check teaser image paths across all photobooks', async ({ page }) => {
        console.log('\n[CHECK] Analyzing teaser images across photobooks...');

        await page.goto('https://dalthaus.net/admin/content?type=photobook');
        await page.waitForLoadState('networkidle');

        const editButtons = page.locator('a[href*="/admin/content/"][href*="/edit"]');
        const photobookCount = await editButtons.count();
        console.log(`[CHECK] Found ${photobookCount} photobooks`);

        const teaserImageMap = {};
        const photobookData = [];

        // Collect teaser image data from each photobook
        for (let i = 0; i < photobookCount; i++) {
            await page.goto('https://dalthaus.net/admin/content?type=photobook');
            await page.waitForLoadState('networkidle');

            const editButton = page.locator('a[href*="/admin/content/"][href*="/edit"]').nth(i);
            const editUrl = await editButton.getAttribute('href');
            const contentId = editUrl.match(/\/content\/(\d+)\//)[1];

            console.log(`\n[CHECK] Checking photobook ${i + 1}/${photobookCount} (ID: ${contentId})...`);

            await editButton.click();
            await page.waitForLoadState('networkidle');

            // Get title
            const titleInput = page.locator('input[name="title"]');
            const title = await titleInput.getAttribute('value');

            // Get teaser image
            const teaserImageSection = page.locator('label:has-text("Teaser Image")').locator('..');
            const teaserImg = teaserImageSection.locator('img').first();
            let teaserPath = null;

            if (await teaserImg.count() > 0) {
                teaserPath = await teaserImg.getAttribute('src');
                console.log(`[CHECK]   Title: ${title}`);
                console.log(`[CHECK]   Teaser: ${teaserPath}`);

                if (!teaserImageMap[teaserPath]) {
                    teaserImageMap[teaserPath] = [];
                }
                teaserImageMap[teaserPath].push({ id: contentId, title });
            } else {
                const errorDiv = teaserImageSection.locator('.text-red-600');
                if (await errorDiv.count() > 0) {
                    const errorText = await errorDiv.textContent();
                    teaserPath = errorText.match(/Image not found: (.+)/)?.[1] || 'ERROR';
                    console.log(`[CHECK]   Title: ${title}`);
                    console.log(`[CHECK]   Teaser: ${teaserPath} (BROKEN)`);
                } else {
                    console.log(`[CHECK]   Title: ${title}`);
                    console.log(`[CHECK]   Teaser: NONE`);
                }
            }

            photobookData.push({ id: contentId, title, teaserPath });
        }

        // Analyze for duplicates
        console.log('\n========================================');
        console.log('[ANALYSIS] Checking for shared teaser images...');
        console.log('========================================\n');

        let sharedCount = 0;
        for (const [imagePath, photobooks] of Object.entries(teaserImageMap)) {
            if (photobooks.length > 1) {
                sharedCount++;
                console.log(`⚠️  SHARED IMAGE: ${imagePath}`);
                console.log(`   Used by ${photobooks.length} photobooks:`);
                photobooks.forEach(pb => {
                    console.log(`     - ID ${pb.id}: ${pb.title}`);
                });
                console.log('');
            }
        }

        if (sharedCount === 0) {
            console.log('✅ No shared teaser images found - each photobook has unique image');
        } else {
            console.log(`⚠️  Found ${sharedCount} teaser image(s) shared across multiple photobooks`);
            console.log('\n[PROBLEM] When one photobook updates its teaser, the shared file gets deleted,');
            console.log('          breaking the teaser for all other photobooks using that same file!');
        }

        // Summary
        console.log('\n========================================');
        console.log('[SUMMARY]');
        console.log('========================================');
        console.log(`Total photobooks: ${photobookData.length}`);
        console.log(`Unique teaser images: ${Object.keys(teaserImageMap).length}`);
        console.log(`Shared images: ${sharedCount}`);
        console.log('');

        photobookData.forEach(pb => {
            const status = pb.teaserPath === 'NONE' ? 'NO TEASER' :
                          pb.teaserPath === 'ERROR' ? 'BROKEN' :
                          pb.teaserPath ? 'OK' : 'UNKNOWN';
            console.log(`- ID ${pb.id}: ${status}`);
        });
    });
});
