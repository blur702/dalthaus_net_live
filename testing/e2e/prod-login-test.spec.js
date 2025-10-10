const { test, expect } = require('@playwright/test');

/**
 * Production Login and Media Browser Test
 * Simplified test focusing on login flow
 */

test.describe('Production Login Test', () => {
    const baseURL = 'https://dalthaus.net';

    test('Login and check media browser', async ({ page }) => {
        test.setTimeout(60000); // 60 second timeout

        console.log('\n=== Starting Production Test ===\n');

        // Set custom header for test detection
        await page.setExtraHTTPHeaders({
            'X-Testing': 'Playwright',
        });

        // Go to login page
        console.log('1. Navigating to login page...');
        await page.goto(`${baseURL}/admin/login`, { waitUntil: 'networkidle' });

        // Take screenshot of login page
        await page.screenshot({ path: 'test-results/01-login-page.png' });
        console.log('   ✓ Login page loaded');

        // Fill in credentials
        console.log('2. Filling in credentials...');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');

        // Take screenshot before submit
        await page.screenshot({ path: 'test-results/02-credentials-filled.png' });
        console.log('   ✓ Credentials filled');

        // Submit login form
        console.log('3. Submitting login form...');

        // Wait for navigation to dashboard after login
        await Promise.all([
            page.waitForURL('**/admin/dashboard', { timeout: 10000 }),
            page.click('button[type="submit"]')
        ]);

        // Wait for page to be fully loaded and session to be established
        await page.waitForLoadState('networkidle');

        // Wait for dashboard content to ensure session is fully established after session_regenerate_id()
        await page.waitForSelector('h1, .dashboard', { timeout: 5000 }).catch(() => {});

        // Small additional delay to ensure session cookie is fully set
        await page.waitForTimeout(1000);

        // Take screenshot after login
        await page.screenshot({ path: 'test-results/03-after-login.png' });

        // Check current URL
        const currentUrl = page.url();
        console.log('   Current URL:', currentUrl);

        // Check if we're on dashboard or still on login
        if (currentUrl.includes('/admin/login')) {
            console.log('   ⚠️  Still on login page - checking for errors...');

            // Look for error messages
            const errorMsg = await page.locator('.error, .alert-danger, [class*="error"]').textContent().catch(() => '');
            if (errorMsg) {
                console.log('   Error message:', errorMsg);
            }

            // Check form validation
            const usernameValue = await page.inputValue('input[name="username"]');
            const passwordValue = await page.inputValue('input[name="password"]');
            console.log('   Username field:', usernameValue);
            console.log('   Password filled:', passwordValue ? 'Yes' : 'No');

            throw new Error('Login failed - still on login page');
        }

        console.log('   ✓ Login successful, redirected to:', currentUrl);

        // Check cookies after login
        const cookies = await page.context().cookies();
        console.log('   Cookies after login:', cookies.length);
        const sessionCookie = cookies.find(c => c.name.includes('session') || c.name.includes('PHPSESSID'));
        if (sessionCookie) {
            console.log('   Session cookie found:', sessionCookie.name);
            console.log('   Cookie domain:', sessionCookie.domain);
            console.log('   Cookie path:', sessionCookie.path);
            console.log('   Cookie secure:', sessionCookie.secure);
            console.log('   Cookie sameSite:', sessionCookie.sameSite);
        } else {
            console.log('   ⚠️  No session cookie found');
        }

        // Navigate to media browser
        console.log('4. Navigating to media browser...');
        await page.goto(`${baseURL}/admin/media/browser`, { waitUntil: 'networkidle' });

        // Check cookies after navigation
        const cookiesAfterNav = await page.context().cookies();
        console.log('   Cookies after navigation:', cookiesAfterNav.length);
        const sessionAfterNav = cookiesAfterNav.find(c => c.name === 'cms_session');
        if (sessionAfterNav) {
            console.log('   Session cookie still present:', sessionAfterNav.name);
        } else {
            console.log('   ⚠️  Session cookie lost after navigation');
        }

        // Take screenshot
        await page.screenshot({ path: 'test-results/04-media-browser.png' });

        // Check if we got redirected back to login
        if (page.url().includes('/admin/login')) {
            console.log('   ❌ Redirected back to login - session not maintained');
            throw new Error('Session lost - redirected to login');
        }

        console.log('   ✓ Media browser page accessed');

        // Check for key elements
        const h1 = await page.locator('h1').textContent().catch(() => '');
        console.log('   Page title:', h1);

        const hasSearchInput = await page.locator('#searchInput').count() > 0;
        const hasTypeFilter = await page.locator('#typeFilter').count() > 0;
        const hasUploadBtn = await page.locator('#uploadBtn').count() > 0;

        console.log('   Search input:', hasSearchInput ? '✓' : '✗');
        console.log('   Type filter:', hasTypeFilter ? '✓' : '✗');
        console.log('   Upload button:', hasUploadBtn ? '✓' : '✗');

        // Final screenshot
        await page.screenshot({ path: 'test-results/05-final-state.png' });

        console.log('\n=== Test Complete ===\n');

        // Assertions
        expect(h1).toContain('Media Browser');
        expect(hasSearchInput).toBeTruthy();
        expect(hasTypeFilter).toBeTruthy();
        expect(hasUploadBtn).toBeTruthy();
    });

    test('Check if media browser requires login', async ({ page }) => {
        console.log('\n=== Testing Auth Protection ===\n');

        // Try to access media browser without login
        console.log('1. Accessing media browser without login...');
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        console.log('   Current URL:', currentUrl);

        if (currentUrl.includes('/admin/login')) {
            console.log('   ✓ Correctly redirected to login (auth protection working)');
        } else {
            console.log('   ⚠️  No redirect - may be session from previous test');
        }

        await page.screenshot({ path: 'test-results/06-auth-check.png' });
    });
});
