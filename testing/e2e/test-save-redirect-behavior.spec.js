const { test, expect } = require('@playwright/test');
const path = require('path');

/**
 * Test Save Redirect Behavior
 * Verifies that after saving content/pages, user is redirected to frontend view
 */

test.describe('Save Redirect Behavior', () => {
    test.beforeEach(async ({ page }) => {
        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    });

    test('Update photobook teaser image and redirect to frontend', async ({ page }) => {
        console.log('\n[TEST] Testing photobook update redirects to frontend...');

        // Go to first photobook
        await page.goto('https://dalthaus.net/admin/content?type=photobook');
        await page.waitForLoadState('networkidle');

        const editButtons = page.locator('a[href*="/admin/content/"][href*="/edit"]');
        const editButtonCount = await editButtons.count();

        if (editButtonCount === 0) {
            console.log('[TEST] ⚠️  SKIPPED - No photobooks found');
            test.skip();
            return;
        }

        // Get the URL for later verification
        const firstEditButton = editButtons.first();
        await firstEditButton.click();
        await page.waitForLoadState('networkidle');

        // Get URL alias to know what frontend URL to expect
        const urlAliasInput = page.locator('input[name="url_alias"]');
        const urlAlias = await urlAliasInput.getAttribute('value');
        console.log(`[TEST] URL alias: ${urlAlias}`);

        // Upload new teaser image
        const testImagePath = path.join(__dirname, '../test-images/test-upload.jpg');
        const fileInput = page.locator('input[name="teaser_image"]');
        await fileInput.setInputFiles(testImagePath);
        console.log('[TEST] New teaser image selected');

        // Click save
        await page.click('button[type="submit"][name="action"][value="save"]');
        console.log('[TEST] Save button clicked');

        // Verify redirect to frontend
        const expectedUrl = `https://dalthaus.net/photobook/${urlAlias}`;
        await page.waitForURL(`**/photobook/${urlAlias}`, { timeout: 10000 });

        const currentUrl = page.url();
        console.log(`[TEST] Redirected to: ${currentUrl}`);
        console.log(`[TEST] Expected: ${expectedUrl}`);

        expect(currentUrl).toBe(expectedUrl);
        console.log('[TEST] ✅ Successfully redirected to frontend view');

        // Verify success message is shown
        const successMessage = page.locator('.bg-green-50, .bg-green-100');
        if (await successMessage.count() > 0) {
            const messageText = await successMessage.textContent();
            console.log(`[TEST] Success message: ${messageText.trim()}`);
        }

        // Verify page is not admin edit page
        expect(currentUrl).not.toContain('/admin/content/');
        expect(currentUrl).not.toContain('/edit');
        console.log('[TEST] ✅ Confirmed not on admin edit page');
    });

    test('Update article and redirect to frontend', async ({ page }) => {
        console.log('\n[TEST] Testing article update redirects to frontend...');

        // Go to first article
        await page.goto('https://dalthaus.net/admin/content?type=article');
        await page.waitForLoadState('networkidle');

        const editButtons = page.locator('a[href*="/admin/content/"][href*="/edit"]');
        const editButtonCount = await editButtons.count();

        if (editButtonCount === 0) {
            console.log('[TEST] ⚠️  SKIPPED - No articles found');
            test.skip();
            return;
        }

        await editButtons.first().click();
        await page.waitForLoadState('networkidle');

        // Get URL alias
        const urlAliasInput = page.locator('input[name="url_alias"]');
        const urlAlias = await urlAliasInput.getAttribute('value');
        console.log(`[TEST] URL alias: ${urlAlias}`);

        // Make a small edit to the title
        const titleInput = page.locator('input[name="title"]');
        const originalTitle = await titleInput.getAttribute('value');
        await titleInput.fill(originalTitle + ' '); // Add space
        await titleInput.fill(originalTitle); // Remove space (revert)
        console.log('[TEST] Made edit to trigger save');

        // Click save
        await page.click('button[type="submit"][name="action"][value="save"]');
        console.log('[TEST] Save button clicked');

        // Verify redirect to frontend
        const expectedUrl = `https://dalthaus.net/article/${urlAlias}`;
        await page.waitForURL(`**/article/${urlAlias}`, { timeout: 10000 });

        const currentUrl = page.url();
        console.log(`[TEST] Redirected to: ${currentUrl}`);

        expect(currentUrl).toBe(expectedUrl);
        console.log('[TEST] ✅ Successfully redirected to frontend view');
    });

    test('Update page and redirect to frontend', async ({ page }) => {
        console.log('\n[TEST] Testing page update redirects to frontend...');

        // Go to pages list
        await page.goto('https://dalthaus.net/admin/pages');
        await page.waitForLoadState('networkidle');

        const editButtons = page.locator('a[href*="/admin/pages/"][href*="/edit"]');
        const editButtonCount = await editButtons.count();

        if (editButtonCount === 0) {
            console.log('[TEST] ⚠️  SKIPPED - No pages found');
            test.skip();
            return;
        }

        await editButtons.first().click();
        await page.waitForLoadState('networkidle');

        // Get URL alias
        const urlAliasInput = page.locator('input[name="url_alias"]');
        const urlAlias = await urlAliasInput.getAttribute('value');
        console.log(`[TEST] URL alias: ${urlAlias}`);

        // Make a small edit
        const titleInput = page.locator('input[name="title"]');
        const originalTitle = await titleInput.getAttribute('value');
        await titleInput.fill(originalTitle + ' ');
        await titleInput.fill(originalTitle);
        console.log('[TEST] Made edit to trigger save');

        // Click save
        await page.click('button[type="submit"][name="action"][value="save"]');
        console.log('[TEST] Save button clicked');

        // Verify redirect to frontend
        const expectedUrl = `https://dalthaus.net/page/${urlAlias}`;
        await page.waitForURL(`**/page/${urlAlias}`, { timeout: 10000 });

        const currentUrl = page.url();
        console.log(`[TEST] Redirected to: ${currentUrl}`);

        expect(currentUrl).toBe(expectedUrl);
        console.log('[TEST] ✅ Successfully redirected to frontend view');
    });
});
