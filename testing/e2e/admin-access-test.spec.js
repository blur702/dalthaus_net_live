const { test, expect } = require('@playwright/test');

test.describe('Admin Access Test via Bypass', () => {
    test('should access admin using admin-access.php bypass', async ({ page }) => {
        console.log('🔄 Testing admin access via bypass method...');

        // Track console messages for debugging
        page.on('console', msg => {
            if (msg.type() === 'log') {
                console.log('Browser:', msg.text());
            } else if (msg.type() === 'error') {
                console.log('Browser Error:', msg.text());
            }
        });

        try {
            // Step 1: Navigate to admin-access.php portal
            await page.goto('https://dalthaus.net/admin-access.php');
            console.log('✓ Accessed admin access portal');

            // Wait for the admin portal to load
            await page.waitForSelector('h1', { timeout: 10000 });
            console.log('✓ Admin access portal loaded');

            // Step 2: Click on Admin Login button
            await page.click('a[href*="admin/login"]');
            console.log('✓ Clicked admin login button');

            // Wait for login form
            await page.waitForSelector('input[name="username"]', { timeout: 10000 });
            console.log('✓ Login form found');

            // Step 3: Login with admin credentials
            await page.fill('input[name="username"]', 'kevin');
            await page.fill('input[name="password"]', '(130Bpm)');
            await page.click('button[type="submit"]');
            console.log('✓ Login form submitted');

            // Wait for dashboard redirect
            await page.waitForURL('**/admin/dashboard', { timeout: 15000 });
            console.log('✓ Successfully logged into admin dashboard');

            // Step 4: Navigate to content management
            await page.goto('https://dalthaus.net/admin/content');
            await page.waitForSelector('h1, h2', { timeout: 10000 });
            console.log('✓ Content management page loaded');

            // Step 5: Test create article link
            const createLink = page.locator('a[href*="create"]').first();
            const createHref = await createLink.getAttribute('href');
            console.log('✓ Found create link:', createHref);

            console.log('🎉 Admin access test completed successfully!');

        } catch (error) {
            console.error('❌ Admin access test failed:', error.message);
            
            // Capture current URL for debugging
            const currentUrl = page.url();
            console.log('Current URL:', currentUrl);
            
            // Take screenshot for debugging
            await page.screenshot({ path: 'testing/results/admin-access-error.png' });
            console.log('Screenshot saved: testing/results/admin-access-error.png');
            
            throw error;
        }
    });
});