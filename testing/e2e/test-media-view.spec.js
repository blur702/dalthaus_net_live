const { test, expect } = require('@playwright/test');

/**
 * Test Media View Functionality
 * Verifies that clicking 'View' on media page works without redirecting to login
 */

test.describe('Media View', () => {
    test.beforeEach(async ({ page }) => {
        // Login
        console.log('[SETUP] Logging in...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('[SETUP] Login successful');
    });

    test('Click View on media page should show media details, not login page', async ({ page }) => {
        console.log('\n[TEST] Testing media view functionality...');

        // Go to media page
        await page.goto('https://dalthaus.net/admin/media');
        await page.waitForLoadState('networkidle');
        console.log('[TEST] Media page loaded');

        // Wait for media items to load
        await page.waitForTimeout(2000);

        // Find "View" links
        const viewLinks = page.locator('a[href*="/admin/media/"]').filter({ hasText: 'View' });
        const viewLinkCount = await viewLinks.count();

        console.log(`[TEST] Found ${viewLinkCount} View links`);

        if (viewLinkCount === 0) {
            console.log('[TEST] ⚠️  SKIPPED - No media items with View links found');
            test.skip();
            return;
        }

        // Get the first view link's href
        const firstViewLink = viewLinks.first();
        const viewHref = await firstViewLink.getAttribute('href');
        console.log(`[TEST] Clicking View link: ${viewHref}`);

        // Click the view link
        await firstViewLink.click();
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        console.log(`[TEST] Current URL after click: ${currentUrl}`);

        // Verify we're NOT on the login page
        expect(currentUrl).not.toContain('/admin/login');
        console.log('[TEST] ✅ Not redirected to login page');

        // Verify we're on a media view page
        expect(currentUrl).toContain('/admin/media/');
        console.log('[TEST] ✅ On media view page');

        // Check for login form (should NOT exist)
        const loginForm = page.locator('input[name="username"]');
        const hasLoginForm = await loginForm.count() > 0;

        if (hasLoginForm) {
            console.log('[TEST] ❌ Login form detected - user was logged out!');
            throw new Error('Login form appeared - authentication was lost');
        }

        console.log('[TEST] ✅ No login form - user remains authenticated');

        // Verify we see media details (not a 404 or error page)
        const pageContent = await page.content();

        if (pageContent.includes('404') || pageContent.includes('Not Found')) {
            console.log('[TEST] ❌ Page shows 404 or Not Found');
            throw new Error('Media view page returned 404');
        }

        console.log('[TEST] ✅ Media view page displays correctly');
    });

    test('Direct navigation to media view URL should work when authenticated', async ({ page }) => {
        console.log('\n[TEST] Testing direct navigation to media view URL...');

        // First, get a valid media ID from the media page
        await page.goto('https://dalthaus.net/admin/media');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        const viewLinks = page.locator('a[href*="/admin/media/"]').filter({ hasText: 'View' });
        const viewLinkCount = await viewLinks.count();

        if (viewLinkCount === 0) {
            console.log('[TEST] ⚠️  SKIPPED - No media items found');
            test.skip();
            return;
        }

        const firstViewLink = viewLinks.first();
        const viewHref = await firstViewLink.getAttribute('href');
        console.log(`[TEST] Found media view URL: ${viewHref}`);

        // Navigate directly to the media view URL
        await page.goto(`https://dalthaus.net${viewHref}`);
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        console.log(`[TEST] Current URL: ${currentUrl}`);

        // Verify we stayed on the media view page
        expect(currentUrl).toContain('/admin/media/');
        expect(currentUrl).not.toContain('/admin/login');
        console.log('[TEST] ✅ Direct navigation successful');

        // Check for login form
        const loginForm = page.locator('input[name="username"]');
        const hasLoginForm = await loginForm.count() > 0;

        expect(hasLoginForm).toBe(false);
        console.log('[TEST] ✅ No login redirect on direct navigation');
    });

    test('Check session persistence across media pages', async ({ page }) => {
        console.log('\n[TEST] Testing session persistence...');

        // Navigate to media list
        await page.goto('https://dalthaus.net/admin/media');
        await page.waitForLoadState('networkidle');
        console.log('[TEST] On media list page');

        // Check if we're still authenticated
        const dashboardLink = page.locator('a[href="/admin/dashboard"]');
        const hasDashboardLink = await dashboardLink.count() > 0;

        if (!hasDashboardLink) {
            console.log('[TEST] ❌ Dashboard link missing - user may not be authenticated');
        } else {
            console.log('[TEST] ✅ Dashboard link present - user authenticated');
        }

        // Now try to view a media item
        await page.waitForTimeout(2000);
        const viewLinks = page.locator('a[href*="/admin/media/"]').filter({ hasText: 'View' });
        const viewLinkCount = await viewLinks.count();

        if (viewLinkCount === 0) {
            console.log('[TEST] ⚠️  SKIPPED - No media items');
            test.skip();
            return;
        }

        await viewLinks.first().click();
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        const isOnLogin = currentUrl.includes('/admin/login');

        console.log(`[TEST] After clicking View, on URL: ${currentUrl}`);
        console.log(`[TEST] Is on login page: ${isOnLogin}`);

        expect(isOnLogin).toBe(false);
        console.log('[TEST] ✅ Session persisted across navigation');
    });
});
