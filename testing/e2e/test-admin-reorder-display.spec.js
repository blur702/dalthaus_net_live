const { test, expect } = require('@playwright/test');

/**
 * Test that reordering is reflected in admin content listings
 */

test.describe('Admin Reorder Display', () => {
    test('Admin content list should reflect saved article reorder', async ({ page }) => {
        console.log('\n[TEST] Testing admin article list reflects reorder...');

        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
        console.log('[STEP 1] ✅ Logged in');

        // Go to articles reorder page
        await page.goto('https://dalthaus.net/admin/articles/reorder');
        await page.waitForLoadState('networkidle');
        console.log('[STEP 2] On articles reorder page');

        // Get current order
        const items = await page.locator('.sortable-item').all();
        console.log(`[STEP 3] Found ${items.length} articles`);

        if (items.length < 3) {
            console.log('[TEST] Not enough articles to test, skipping');
            test.skip();
            return;
        }

        const articleData = [];
        for (const item of items) {
            const id = await item.getAttribute('data-id');
            const title = await item.locator('h3').first().textContent();
            articleData.push({ id, title: title.trim() });
        }

        console.log('[STEP 3] Current order (first 5):');
        for (let i = 0; i < Math.min(5, articleData.length); i++) {
            console.log(`  ${i + 1}. ID ${articleData[i].id}: "${articleData[i].title}"`);
        }

        // Reverse the order
        const reversedOrder = {};
        for (let i = 0; i < articleData.length; i++) {
            reversedOrder[articleData[i].id] = articleData.length - i;
        }

        console.log('[STEP 4] Reversing order...');

        // Save the new order
        const csrfToken = await page.locator('input[name="_token"]').first().getAttribute('value');
        const formData = new URLSearchParams();
        formData.append('_token', csrfToken);
        formData.append('order', JSON.stringify(reversedOrder));

        const response = await page.request.post('https://dalthaus.net/admin/articles/update-order', {
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            data: formData.toString()
        });

        const responseData = JSON.parse(await response.text());
        console.log('[STEP 4] Save response:', responseData);
        expect(responseData.success).toBe(true);
        console.log('[STEP 4] ✅ Order saved');

        // Go to admin content list (articles only)
        await page.goto('https://dalthaus.net/admin/content?type=article');
        await page.waitForLoadState('networkidle');
        console.log('[STEP 5] On admin content list');

        // Get article titles from the list
        const listRows = await page.locator('tbody tr').all();
        console.log(`[STEP 5] Found ${listRows.length} articles in admin list`);

        const adminTitles = [];
        for (const row of listRows) {
            // Title is in the first column, inside a link within nested divs
            const titleLink = row.locator('td').first().locator('.text-sm.font-medium a');
            const title = await titleLink.textContent();
            adminTitles.push(title.trim());
        }

        console.log('[STEP 5] Admin list order (first 5):');
        for (let i = 0; i < Math.min(5, adminTitles.length); i++) {
            console.log(`  ${i + 1}. "${adminTitles[i]}"`);
        }

        // Expected order is reversed
        const expectedTitles = [...articleData].reverse().map(a => a.title);

        console.log('\n[STEP 6] Comparing orders...');
        console.log('[STEP 6] Expected (reversed) order (first 5):');
        for (let i = 0; i < Math.min(5, expectedTitles.length); i++) {
            console.log(`  ${i + 1}. "${expectedTitles[i]}"`);
        }

        // Check if first 3 articles match the expected reversed order
        const checkCount = Math.min(3, expectedTitles.length, adminTitles.length);
        let matchCount = 0;

        for (let i = 0; i < checkCount; i++) {
            if (adminTitles[i] === expectedTitles[i]) {
                console.log(`[STEP 6] ✅ Position ${i + 1} matches: "${adminTitles[i]}"`);
                matchCount++;
            } else {
                console.log(`[STEP 6] ❌ Position ${i + 1} mismatch:`);
                console.log(`       Expected: "${expectedTitles[i]}"`);
                console.log(`       Got:      "${adminTitles[i]}"`);
            }
        }

        if (matchCount !== checkCount) {
            throw new Error(`Only ${matchCount}/${checkCount} positions matched. Admin list does not reflect reorder.`);
        }

        console.log(`\n[TEST] ✅ SUCCESS: All ${checkCount} checked positions match!`);
        console.log('[TEST] Admin content list correctly reflects the saved reorder');
    });

    test('Admin content list should reflect saved photobook reorder', async ({ page }) => {
        console.log('\n[TEST] Testing admin photobook list reflects reorder...');

        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
        console.log('[STEP 1] ✅ Logged in');

        // Go to photobooks reorder page
        await page.goto('https://dalthaus.net/admin/photobooks/reorder');
        await page.waitForLoadState('networkidle');
        console.log('[STEP 2] On photobooks reorder page');

        // Get current order
        const items = await page.locator('.sortable-item').all();
        console.log(`[STEP 3] Found ${items.length} photobooks`);

        if (items.length < 2) {
            console.log('[TEST] Not enough photobooks to test, skipping');
            test.skip();
            return;
        }

        const photobookData = [];
        for (const item of items) {
            const id = await item.getAttribute('data-id');
            const title = await item.locator('h3').first().textContent();
            photobookData.push({ id, title: title.trim() });
        }

        console.log('[STEP 3] Current order:');
        photobookData.forEach((photobook, index) => {
            console.log(`  ${index + 1}. ID ${photobook.id}: "${photobook.title}"`);
        });

        // Reverse the order
        const reversedOrder = {};
        for (let i = 0; i < photobookData.length; i++) {
            reversedOrder[photobookData[i].id] = photobookData.length - i;
        }

        console.log('[STEP 4] Reversing order...');

        // Save the new order
        const csrfToken = await page.locator('input[name="_token"]').first().getAttribute('value');
        const formData = new URLSearchParams();
        formData.append('_token', csrfToken);
        formData.append('order', JSON.stringify(reversedOrder));

        const response = await page.request.post('https://dalthaus.net/admin/photobooks/update-order', {
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            data: formData.toString()
        });

        const responseData = JSON.parse(await response.text());
        console.log('[STEP 4] Save response:', responseData);
        expect(responseData.success).toBe(true);
        console.log('[STEP 4] ✅ Order saved');

        // Go to admin content list (photobooks only)
        await page.goto('https://dalthaus.net/admin/content?type=photobook');
        await page.waitForLoadState('networkidle');
        console.log('[STEP 5] On admin content list');

        // Get photobook titles from the list
        const listRows = await page.locator('tbody tr').all();
        console.log(`[STEP 5] Found ${listRows.length} photobooks in admin list`);

        const adminTitles = [];
        for (const row of listRows) {
            // Title is in the first column, inside a link within nested divs
            const titleLink = row.locator('td').first().locator('.text-sm.font-medium a');
            const title = await titleLink.textContent();
            adminTitles.push(title.trim());
        }

        console.log('[STEP 5] Admin list order:');
        adminTitles.forEach((title, index) => {
            console.log(`  ${index + 1}. "${title}"`);
        });

        // Expected order is reversed
        const expectedTitles = [...photobookData].reverse().map(p => p.title);

        console.log('\n[STEP 6] Comparing orders...');
        console.log('[STEP 6] Expected (reversed) order:');
        expectedTitles.forEach((title, index) => {
            console.log(`  ${index + 1}. "${title}"`);
        });

        // Check if all photobooks match the expected reversed order
        const checkCount = Math.min(photobookData.length, adminTitles.length);
        let matchCount = 0;

        for (let i = 0; i < checkCount; i++) {
            if (adminTitles[i] === expectedTitles[i]) {
                console.log(`[STEP 6] ✅ Position ${i + 1} matches: "${adminTitles[i]}"`);
                matchCount++;
            } else {
                console.log(`[STEP 6] ❌ Position ${i + 1} mismatch:`);
                console.log(`       Expected: "${expectedTitles[i]}"`);
                console.log(`       Got:      "${adminTitles[i]}"`);
            }
        }

        if (matchCount !== checkCount) {
            throw new Error(`Only ${matchCount}/${checkCount} positions matched. Admin list does not reflect reorder.`);
        }

        console.log(`\n[TEST] ✅ SUCCESS: All ${checkCount} positions match!`);
        console.log('[TEST] Admin content list correctly reflects the saved reorder');
    });
});
