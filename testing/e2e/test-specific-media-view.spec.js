const { test, expect } = require('@playwright/test');

/**
 * Test specific media ID 198
 */

test.describe('Test Media ID 198', () => {
    test('Access /admin/media/198 while logged in', async ({ page }) => {
        console.log('\n[TEST] Testing media ID 198...');

        // Login
        console.log('[TEST] Logging in...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('[TEST] Logged in successfully');

        // Try to access media ID 198
        console.log('[TEST] Navigating to /admin/media/198...');
        await page.goto('https://dalthaus.net/admin/media/198');
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        console.log(`[TEST] Current URL: ${currentUrl}`);

        // Check if we're on login page
        const isOnLogin = currentUrl.includes('/admin/login');
        console.log(`[TEST] Is on login page: ${isOnLogin}`);

        // Check if login form exists
        const loginForm = page.locator('input[name="username"]');
        const hasLoginForm = await loginForm.count() > 0;
        console.log(`[TEST] Has login form: ${hasLoginForm}`);

        // Check page content
        const pageTitle = await page.title();
        console.log(`[TEST] Page title: ${pageTitle}`);

        const h1 = page.locator('h1').first();
        if (await h1.count() > 0) {
            const h1Text = await h1.textContent();
            console.log(`[TEST] H1 text: ${h1Text}`);
        }

        // Get page HTML to see what's actually rendered
        const bodyText = await page.locator('body').textContent();
        const snippet = bodyText.substring(0, 200);
        console.log(`[TEST] Body snippet: ${snippet}`);

        if (isOnLogin || hasLoginForm) {
            console.log('[TEST] ❌ REDIRECTED TO LOGIN PAGE');

            // Check if there's a specific error or reason
            const errorMessage = page.locator('.error, .alert, [role="alert"]');
            if (await errorMessage.count() > 0) {
                const errorText = await errorMessage.textContent();
                console.log(`[TEST] Error message: ${errorText}`);
            }
        } else {
            console.log('[TEST] ✅ Not redirected to login');
        }

        expect(isOnLogin).toBe(false);
        expect(hasLoginForm).toBe(false);
    });

    test('Check route pattern matching for /admin/media/198', async ({ page }) => {
        console.log('\n[TEST] Checking route pattern...');

        // Login first
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });

        // Navigate and capture network traffic
        const responses = [];
        page.on('response', response => {
            responses.push({
                url: response.url(),
                status: response.status(),
                headers: response.headers()
            });
        });

        await page.goto('https://dalthaus.net/admin/media/198');
        await page.waitForLoadState('networkidle');

        console.log('[TEST] Network responses:');
        responses.forEach(r => {
            if (r.url.includes('admin/media')) {
                console.log(`  ${r.status} - ${r.url}`);
                if (r.headers.location) {
                    console.log(`    Redirect to: ${r.headers.location}`);
                }
            }
        });

        const currentUrl = page.url();
        console.log(`[TEST] Final URL: ${currentUrl}`);
    });
});
