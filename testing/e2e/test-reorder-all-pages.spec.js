const { test, expect } = require('@playwright/test');

/**
 * Comprehensive test: Verify reorder is reflected on ALL listing pages
 * - Admin content list
 * - Front page (home)
 * - Public articles page
 * - Public photobooks page
 */

test.describe('Reorder Reflection on All Pages', () => {
    test('Article reorder should be reflected on all listing pages', async ({ page }) => {
        console.log('\n[TEST] Testing article reorder is reflected everywhere...');

        // STEP 1: Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
        console.log('[STEP 1] ✅ Logged in');

        // STEP 2: Go to articles reorder page and save a specific order
        await page.goto('https://dalthaus.net/admin/articles/reorder');
        await page.waitForLoadState('networkidle');
        console.log('[STEP 2] On articles reorder page');

        // Get current articles
        const items = await page.locator('.sortable-item').all();
        console.log(`[STEP 2] Found ${items.length} articles`);

        if (items.length < 3) {
            console.log('[TEST] Not enough articles to test');
            test.skip();
            return;
        }

        const articleData = [];
        for (const item of items) {
            const id = await item.getAttribute('data-id');
            const title = await item.locator('h3').first().textContent();
            articleData.push({ id, title: title.trim() });
        }

        console.log('[STEP 2] Current order (first 5):');
        for (let i = 0; i < Math.min(5, articleData.length); i++) {
            console.log(`  ${i + 1}. "${articleData[i].title}"`);
        }

        // STEP 3: Set a specific order (reverse it)
        const reversedOrder = {};
        for (let i = 0; i < articleData.length; i++) {
            reversedOrder[articleData[i].id] = articleData.length - i;
        }

        const csrfToken = await page.locator('input[name="_token"]').first().getAttribute('value');
        const formData = new URLSearchParams();
        formData.append('_token', csrfToken);
        formData.append('order', JSON.stringify(reversedOrder));

        const response = await page.request.post('https://dalthaus.net/admin/articles/update-order', {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: formData.toString()
        });

        const responseData = JSON.parse(await response.text());
        console.log('[STEP 3] Save response:', responseData);
        expect(responseData.success).toBe(true);
        console.log('[STEP 3] ✅ Order saved to database');

        const expectedOrder = [...articleData].reverse();
        console.log('[STEP 3] Expected order (first 5):');
        for (let i = 0; i < Math.min(5, expectedOrder.length); i++) {
            console.log(`  ${i + 1}. "${expectedOrder[i].title}"`);
        }

        // STEP 4: Check admin content list
        console.log('\n[STEP 4] Checking admin content list...');
        await page.goto('https://dalthaus.net/admin/content?type=article');
        await page.waitForLoadState('networkidle');

        const adminRows = await page.locator('tbody tr').all();
        const adminTitles = [];
        for (const row of adminRows) {
            const titleLink = row.locator('td').first().locator('.text-sm.font-medium a');
            const title = await titleLink.textContent();
            adminTitles.push(title.trim());
        }

        console.log('[STEP 4] Admin list order (first 5):');
        for (let i = 0; i < Math.min(5, adminTitles.length); i++) {
            console.log(`  ${i + 1}. "${adminTitles[i]}"`);
        }

        // STEP 5: Check front page (home)
        console.log('\n[STEP 5] Checking front page...');
        await page.goto('https://dalthaus.net/');
        await page.waitForLoadState('networkidle');

        const homeArticles = await page.locator('article h2 a, article h3 a').all();
        const homeTitles = [];
        for (const link of homeArticles) {
            const title = await link.textContent();
            if (title && title.trim()) {
                homeTitles.push(title.trim());
            }
        }

        console.log('[STEP 5] Front page articles (first 5):');
        for (let i = 0; i < Math.min(5, homeTitles.length); i++) {
            console.log(`  ${i + 1}. "${homeTitles[i]}"`);
        }

        // STEP 6: Check public articles page
        console.log('\n[STEP 6] Checking public articles page...');
        await page.goto('https://dalthaus.net/articles');
        await page.waitForLoadState('networkidle');

        const publicArticles = await page.locator('article h2 a, article h3 a').all();
        const publicTitles = [];
        for (const link of publicArticles) {
            const title = await link.textContent();
            if (title && title.trim()) {
                publicTitles.push(title.trim());
            }
        }

        console.log('[STEP 6] Public articles page (first 5):');
        for (let i = 0; i < Math.min(5, publicTitles.length); i++) {
            console.log(`  ${i + 1}. "${publicTitles[i]}"`);
        }

        // STEP 7: Compare all pages to expected order
        console.log('\n[STEP 7] Comparing all pages to expected order...');

        const checkCount = Math.min(3, expectedOrder.length);
        let allMatch = true;

        // Check admin list
        console.log('\n[STEP 7] Admin list comparison:');
        for (let i = 0; i < checkCount; i++) {
            const matches = adminTitles[i] === expectedOrder[i].title;
            console.log(`  Position ${i + 1}: ${matches ? '✅' : '❌'} Expected "${expectedOrder[i].title}", got "${adminTitles[i]}"`);
            if (!matches) allMatch = false;
        }

        // Check front page
        console.log('\n[STEP 7] Front page comparison:');
        for (let i = 0; i < checkCount; i++) {
            const matches = homeTitles[i] === expectedOrder[i].title;
            console.log(`  Position ${i + 1}: ${matches ? '✅' : '❌'} Expected "${expectedOrder[i].title}", got "${homeTitles[i]}"`);
            if (!matches) allMatch = false;
        }

        // Check public articles page
        console.log('\n[STEP 7] Public articles page comparison:');
        for (let i = 0; i < checkCount; i++) {
            const matches = publicTitles[i] === expectedOrder[i].title;
            console.log(`  Position ${i + 1}: ${matches ? '✅' : '❌'} Expected "${expectedOrder[i].title}", got "${publicTitles[i]}"`);
            if (!matches) allMatch = false;
        }

        if (!allMatch) {
            throw new Error('Reorder is NOT reflected on all pages. See comparison above.');
        }

        console.log('\n[TEST] ✅ SUCCESS: Reorder is correctly reflected on ALL pages!');
    });

    test('Photobook reorder should be reflected on all listing pages', async ({ page }) => {
        console.log('\n[TEST] Testing photobook reorder is reflected everywhere...');

        // STEP 1: Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
        console.log('[STEP 1] ✅ Logged in');

        // STEP 2: Go to photobooks reorder page
        await page.goto('https://dalthaus.net/admin/photobooks/reorder');
        await page.waitForLoadState('networkidle');
        console.log('[STEP 2] On photobooks reorder page');

        const items = await page.locator('.sortable-item').all();
        console.log(`[STEP 2] Found ${items.length} photobooks`);

        if (items.length < 2) {
            console.log('[TEST] Not enough photobooks to test');
            test.skip();
            return;
        }

        const photobookData = [];
        for (const item of items) {
            const id = await item.getAttribute('data-id');
            const title = await item.locator('h3').first().textContent();
            photobookData.push({ id, title: title.trim() });
        }

        console.log('[STEP 2] Current order:');
        photobookData.forEach((pb, index) => {
            console.log(`  ${index + 1}. "${pb.title}"`);
        });

        // STEP 3: Reverse the order
        const reversedOrder = {};
        for (let i = 0; i < photobookData.length; i++) {
            reversedOrder[photobookData[i].id] = photobookData.length - i;
        }

        const csrfToken = await page.locator('input[name="_token"]').first().getAttribute('value');
        const formData = new URLSearchParams();
        formData.append('_token', csrfToken);
        formData.append('order', JSON.stringify(reversedOrder));

        const response = await page.request.post('https://dalthaus.net/admin/photobooks/update-order', {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: formData.toString()
        });

        const responseData = JSON.parse(await response.text());
        console.log('[STEP 3] Save response:', responseData);
        expect(responseData.success).toBe(true);
        console.log('[STEP 3] ✅ Order saved to database');

        const expectedOrder = [...photobookData].reverse();
        console.log('[STEP 3] Expected order:');
        expectedOrder.forEach((pb, index) => {
            console.log(`  ${index + 1}. "${pb.title}"`);
        });

        // STEP 4: Check admin content list
        console.log('\n[STEP 4] Checking admin content list...');
        await page.goto('https://dalthaus.net/admin/content?type=photobook');
        await page.waitForLoadState('networkidle');

        const adminRows = await page.locator('tbody tr').all();
        const adminTitles = [];
        for (const row of adminRows) {
            const titleLink = row.locator('td').first().locator('.text-sm.font-medium a');
            const title = await titleLink.textContent();
            adminTitles.push(title.trim());
        }

        console.log('[STEP 4] Admin list order:');
        adminTitles.forEach((title, index) => {
            console.log(`  ${index + 1}. "${title}"`);
        });

        // STEP 5: Check front page
        console.log('\n[STEP 5] Checking front page...');
        await page.goto('https://dalthaus.net/');
        await page.waitForLoadState('networkidle');

        const homePhotobooks = await page.locator('article h2 a, article h3 a').all();
        const homeTitles = [];
        for (const link of homePhotobooks) {
            const title = await link.textContent();
            if (title && title.trim()) {
                homeTitles.push(title.trim());
            }
        }

        console.log('[STEP 5] Front page photobooks found:');
        homeTitles.forEach((title, index) => {
            console.log(`  ${index + 1}. "${title}"`);
        });

        // STEP 6: Check public photobooks page
        console.log('\n[STEP 6] Checking public photobooks page...');
        await page.goto('https://dalthaus.net/photobooks');
        await page.waitForLoadState('networkidle');

        const publicPhotobooks = await page.locator('article h2 a, article h3 a').all();
        const publicTitles = [];
        for (const link of publicPhotobooks) {
            const title = await link.textContent();
            if (title && title.trim()) {
                publicTitles.push(title.trim());
            }
        }

        console.log('[STEP 6] Public photobooks page:');
        publicTitles.forEach((title, index) => {
            console.log(`  ${index + 1}. "${title}"`);
        });

        // STEP 7: Compare all pages
        console.log('\n[STEP 7] Comparing all pages to expected order...');

        let allMatch = true;

        // Check admin list
        console.log('\n[STEP 7] Admin list comparison:');
        for (let i = 0; i < expectedOrder.length; i++) {
            const matches = adminTitles[i] === expectedOrder[i].title;
            console.log(`  Position ${i + 1}: ${matches ? '✅' : '❌'} Expected "${expectedOrder[i].title}", got "${adminTitles[i]}"`);
            if (!matches) allMatch = false;
        }

        // Check public photobooks page
        console.log('\n[STEP 7] Public photobooks page comparison:');
        for (let i = 0; i < expectedOrder.length; i++) {
            const matches = publicTitles[i] === expectedOrder[i].title;
            console.log(`  Position ${i + 1}: ${matches ? '✅' : '❌'} Expected "${expectedOrder[i].title}", got "${publicTitles[i]}"`);
            if (!matches) allMatch = false;
        }

        if (!allMatch) {
            throw new Error('Reorder is NOT reflected on all pages. See comparison above.');
        }

        console.log('\n[TEST] ✅ SUCCESS: Reorder is correctly reflected on ALL pages!');
    });
});
