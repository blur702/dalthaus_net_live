const { test, expect } = require('@playwright/test');

/**
 * Test Draft Content Viewing
 * Verifies that authenticated admins can view draft content via "View Live" button
 * and that non-authenticated users cannot see draft content
 */

test.describe('Draft Content Viewing', () => {
    test('Authenticated admin can view draft article via View Live button', async ({ page }) => {
        console.log('\n[TEST] Testing authenticated admin can view draft article...');

        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('[TEST] Logged in successfully');

        // Go to articles list
        await page.goto('https://dalthaus.net/admin/content?type=article');
        await page.waitForLoadState('networkidle');

        // Find a draft article or create one
        const rows = page.locator('tr').filter({ hasText: 'Draft' });
        const draftCount = await rows.count();

        if (draftCount === 0) {
            console.log('[TEST] No draft articles found, creating one...');

            // Create a draft article
            await page.goto('https://dalthaus.net/admin/content/create?type=article');
            await page.waitForLoadState('networkidle');

            const uniqueTitle = `Test Draft Article ${Date.now()}`;
            await page.fill('input[name="title"]', uniqueTitle);
            await page.fill('input[name="url_alias"]', `test-draft-${Date.now()}`);
            await page.fill('textarea[name="body"]', 'This is a test draft article content.');

            // Save as draft (not publish)
            await page.click('button[type="submit"][name="action"][value="save"]');
            await page.waitForLoadState('networkidle');

            console.log('[TEST] Created draft article');

            // Go back to admin to access it
            await page.goto('https://dalthaus.net/admin/content?type=article');
            await page.waitForLoadState('networkidle');
        }

        // Find the first draft article
        const draftRows = page.locator('tr').filter({ hasText: 'Draft' });
        const firstDraftRow = draftRows.first();
        const editLink = firstDraftRow.locator('a[href*="/admin/content/"][href*="/edit"]');

        await editLink.click();
        await page.waitForLoadState('networkidle');

        const urlAliasInput = page.locator('input[name="url_alias"]');
        const urlAlias = await urlAliasInput.getAttribute('value');
        console.log(`[TEST] Editing draft article with alias: ${urlAlias}`);

        // Click "View Live" button (green button with external link icon)
        const viewLiveButton = page.locator('a[href^="/article/"][target="_blank"]');

        if (await viewLiveButton.count() === 0) {
            console.log('[TEST] ❌ No "View Live" button found');
            throw new Error('View Live button not found on edit page');
        }

        const viewLiveHref = await viewLiveButton.getAttribute('href');
        console.log(`[TEST] View Live button href: ${viewLiveHref}`);

        // Open in same window for testing (ignore target="_blank")
        await page.goto(`https://dalthaus.net${viewLiveHref}`);
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        console.log(`[TEST] Navigated to: ${currentUrl}`);

        // Verify we're on the article page (not 404)
        const statusCode = await page.evaluate(() => {
            return document.querySelector('h1')?.textContent || '';
        });

        expect(currentUrl).toContain('/article/');
        expect(currentUrl).not.toContain('404');
        console.log('[TEST] ✅ Draft article is accessible to authenticated admin');

        // Verify the page loaded successfully (not a 404 page)
        const heading = page.locator('h1').first();
        const headingText = await heading.textContent();
        console.log(`[TEST] Page heading: ${headingText}`);
        expect(headingText).not.toContain('Not Found');
        expect(headingText).not.toContain('404');

        console.log('[TEST] ✅ Draft article displays correctly');
    });

    test('Non-authenticated user cannot view draft article', async ({ context }) => {
        console.log('\n[TEST] Testing non-authenticated user cannot view draft article...');

        // First, get a draft article URL while logged in
        const adminPage = await context.newPage();
        await adminPage.goto('https://dalthaus.net/admin/login');
        await adminPage.fill('input[name="username"]', 'kevin');
        await adminPage.fill('input[name="password"]', '(130Bpm)');
        await adminPage.click('button[type="submit"]');
        await adminPage.waitForURL('**/admin/dashboard', { timeout: 10000 });

        await adminPage.goto('https://dalthaus.net/admin/content?type=article');
        await adminPage.waitForLoadState('networkidle');

        const draftRows = adminPage.locator('tr').filter({ hasText: 'Draft' });
        const draftCount = await draftRows.count();

        if (draftCount === 0) {
            console.log('[TEST] ⚠️  SKIPPED - No draft articles to test with');
            await adminPage.close();
            test.skip();
            return;
        }

        const firstDraftRow = draftRows.first();
        const editLink = firstDraftRow.locator('a[href*="/admin/content/"][href*="/edit"]');
        await editLink.click();
        await adminPage.waitForLoadState('networkidle');

        const urlAliasInput = adminPage.locator('input[name="url_alias"]');
        const urlAlias = await urlAliasInput.getAttribute('value');
        const draftUrl = `https://dalthaus.net/article/${urlAlias}`;
        console.log(`[TEST] Draft article URL: ${draftUrl}`);

        await adminPage.close();

        // Now try to access the draft URL in a new incognito context (not logged in)
        const incognitoContext = await context.browser().newContext();
        const publicPage = await incognitoContext.newPage();

        await publicPage.goto(draftUrl);
        await publicPage.waitForLoadState('networkidle');

        const currentUrl = publicPage.url();
        console.log(`[TEST] Non-authenticated user navigated to: ${currentUrl}`);

        // Verify we get a 404 response
        const heading = publicPage.locator('h1').first();
        const headingText = await heading.textContent();
        console.log(`[TEST] Page heading: ${headingText}`);

        // Should see 404 page
        expect(headingText).toContain('404');
        console.log('[TEST] ✅ Draft article returns 404 for non-authenticated user');

        await publicPage.close();
        await incognitoContext.close();
    });

    test('Authenticated admin can view draft photobook via View Live button', async ({ page }) => {
        console.log('\n[TEST] Testing authenticated admin can view draft photobook...');

        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });

        // Go to photobooks list
        await page.goto('https://dalthaus.net/admin/content?type=photobook');
        await page.waitForLoadState('networkidle');

        // Find a draft photobook or create one
        const rows = page.locator('tr').filter({ hasText: 'Draft' });
        const draftCount = await rows.count();

        if (draftCount === 0) {
            console.log('[TEST] No draft photobooks found, creating one...');

            // Create a draft photobook
            await page.goto('https://dalthaus.net/admin/content/create?type=photobook');
            await page.waitForLoadState('networkidle');

            const uniqueTitle = `Test Draft Photobook ${Date.now()}`;
            await page.fill('input[name="title"]', uniqueTitle);
            await page.fill('input[name="url_alias"]', `test-draft-pb-${Date.now()}`);
            await page.fill('textarea[name="body"]', 'This is a test draft photobook content.');

            // Save as draft
            await page.click('button[type="submit"][name="action"][value="save"]');
            await page.waitForLoadState('networkidle');

            console.log('[TEST] Created draft photobook');

            await page.goto('https://dalthaus.net/admin/content?type=photobook');
            await page.waitForLoadState('networkidle');
        }

        // Find the first draft photobook
        const draftRows = page.locator('tr').filter({ hasText: 'Draft' });
        const firstDraftRow = draftRows.first();
        const editLink = firstDraftRow.locator('a[href*="/admin/content/"][href*="/edit"]');

        await editLink.click();
        await page.waitForLoadState('networkidle');

        const urlAliasInput = page.locator('input[name="url_alias"]');
        const urlAlias = await urlAliasInput.getAttribute('value');
        console.log(`[TEST] Editing draft photobook with alias: ${urlAlias}`);

        // Click "View Live" button
        const viewLiveButton = page.locator('a[href^="/photobook/"][target="_blank"]');

        if (await viewLiveButton.count() === 0) {
            console.log('[TEST] ❌ No "View Live" button found');
            throw new Error('View Live button not found on edit page');
        }

        const viewLiveHref = await viewLiveButton.getAttribute('href');
        console.log(`[TEST] View Live button href: ${viewLiveHref}`);

        await page.goto(`https://dalthaus.net${viewLiveHref}`);
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        console.log(`[TEST] Navigated to: ${currentUrl}`);

        expect(currentUrl).toContain('/photobook/');
        expect(currentUrl).not.toContain('404');
        console.log('[TEST] ✅ Draft photobook is accessible to authenticated admin');

        const heading = page.locator('h1').first();
        const headingText = await heading.textContent();
        console.log(`[TEST] Page heading: ${headingText}`);
        expect(headingText).not.toContain('Not Found');
        expect(headingText).not.toContain('404');

        console.log('[TEST] ✅ Draft photobook displays correctly');
    });
});
